#!/usr/bin/env python3
"""Optimizador de horarios de Moctezuma Básica.

Lee un JSON de entrada y produce propuestas CP-SAT. No accede a la base de datos.
"""
from __future__ import annotations

import json
import sys
from collections import defaultdict
from pathlib import Path

try:
    from ortools.sat.python import cp_model
except Exception as exc:  # pragma: no cover - mensaje consumido por Laravel
    print(json.dumps({"ok": False, "error": f"OR-Tools no está instalado: {exc}"}, ensure_ascii=False))
    sys.exit(2)


def solve(payload: dict, objective_name: str) -> dict:
    days = payload["days"]
    hours = [h for h in payload["hours"] if not h.get("blocked")]
    assignments = payload["assignments"]
    locked = payload.get("locked", [])
    external_locked = payload.get("external_locked", [])
    availability = payload.get("availability", {})
    teacher_configs = payload.get("teacher_configs", {})
    rules = payload.get("rules", {})
    seconds = int(payload.get("seconds_per_objective", 12))

    def rule_weight(code: str, default: int) -> int:
        row = rules.get(code)
        if row is None:
            return 0
        try:
            return max(0, int(row.get("weight", default)))
        except (TypeError, ValueError):
            return default

    slots = [(d["id"], h["id"], d["order"], h["order"]) for d in days for h in hours]
    slot_index = {(d, h): i for i, (d, h, _, _) in enumerate(slots)}

    locked_group = defaultdict(int)
    locked_teacher = defaultdict(int)
    locked_assignment_count = defaultdict(int)
    locked_teacher_daily = defaultdict(set)
    locked_assignment_slots = set()
    for row in locked:
        key = (int(row["day_id"]), int(row["hour_id"]))
        # Coenseñanza puede producir dos filas para el mismo grupo y actividad.
        # Para la capacidad del grupo cuenta como una sola sesión; para la carga
        # de cada docente sí se conserva cada participación.
        locked_group[(int(row["group_id"]), *key)] = 1
        if row.get("teacher_id"):
            teacher = int(row["teacher_id"])
            locked_teacher[(teacher, *key)] += 1
            locked_teacher_daily[(teacher, key[0])].add(key[1])
        if row.get("assignment_id"):
            assignment_slot = (int(row["assignment_id"]), int(row["group_id"]), *key)
            if assignment_slot not in locked_assignment_slots:
                locked_assignment_count[int(row["assignment_id"])] += 1
                locked_assignment_slots.add(assignment_slot)
    for row in external_locked:
        teacher = int(row["teacher_id"])
        day_id = int(row["day_id"])
        hour_id = int(row["hour_id"])
        locked_teacher[(teacher, day_id, hour_id)] += 1
        locked_teacher_daily[(teacher, day_id)].add(hour_id)

    sessions = []
    assignment_by_id = {int(a["id"]): a for a in assignments}
    for assignment in assignments:
        aid = int(assignment["id"])
        remaining = max(0, int(assignment["required"]) - locked_assignment_count[aid])
        for seq in range(remaining):
            sessions.append({"key": f"{aid}-{seq}", "assignment": assignment})

    model = cp_model.CpModel()
    x = {}
    unassigned = {}

    def avail_state(teacher_id: int | None, day_id: int, hour_id: int) -> str:
        if not teacher_id:
            return "disponible"
        return availability.get(f"{teacher_id}:{day_id}:{hour_id}", "disponible")

    for si, session in enumerate(sessions):
        assignment = session["assignment"]
        teacher = assignment.get("teacher_id")
        group = int(assignment["group_id"])
        vars_for_session = []
        for sl, (day_id, hour_id, _, hour_order) in enumerate(slots):
            if locked_group[(group, day_id, hour_id)] > 0:
                continue
            if teacher:
                cfg = teacher_configs.get(str(int(teacher)), {})
                first_order = cfg.get("first_order")
                last_order = cfg.get("last_order")
                if first_order is not None and int(hour_order) < int(first_order):
                    continue
                if last_order is not None and int(hour_order) > int(last_order):
                    continue
            state = avail_state(int(teacher) if teacher else None, day_id, hour_id)
            if state == "no_disponible":
                continue
            var = model.NewBoolVar(f"x_{si}_{sl}")
            x[(si, sl)] = var
            vars_for_session.append(var)
        un = model.NewBoolVar(f"unassigned_{si}")
        unassigned[si] = un
        model.Add(sum(vars_for_session) + un == 1)

    # Un grupo solo puede recibir una actividad independiente por bloque.
    for group in {int(a["group_id"]) for a in assignments}:
        for sl, (day_id, hour_id, _, _) in enumerate(slots):
            vars_here = [x[(si, sl)] for si, s in enumerate(sessions)
                         if (si, sl) in x and int(s["assignment"]["group_id"]) == group]
            capacity = max(0, 1 - locked_group[(group, day_id, hour_id)])
            if vars_here:
                model.Add(sum(vars_here) <= capacity)

    # El docente puede atender varios grupos simultáneos hasta su máximo configurable.
    teacher_slot_vars = defaultdict(list)
    teacher_day_vars = defaultdict(list)
    for (si, sl), var in x.items():
        a = sessions[si]["assignment"]
        teacher = a.get("teacher_id")
        if teacher:
            day_id, hour_id, _, _ = slots[sl]
            teacher = int(teacher)
            teacher_slot_vars[(teacher, day_id, hour_id)].append(var)
            teacher_day_vars[(teacher, day_id)].append(var)

    simultaneous_excess = []
    for (teacher, day_id, hour_id), variables in teacher_slot_vars.items():
        cfg = teacher_configs.get(str(teacher), {})
        maximum = max(1, int(cfg.get("max_simultaneous", 2)))
        locked_count = locked_teacher[(teacher, day_id, hour_id)]
        model.Add(sum(variables) + locked_count <= maximum)
        excess = model.NewIntVar(0, maximum, f"simex_{teacher}_{day_id}_{hour_id}")
        model.Add(excess >= sum(variables) + locked_count - 1)
        simultaneous_excess.append(excess)

    for (teacher, day_id), variables in teacher_day_vars.items():
        cfg = teacher_configs.get(str(teacher), {})
        max_daily = max(1, int(cfg.get("max_daily", 6)))
        locked_daily = len(locked_teacher_daily[(teacher, day_id)])
        model.Add(sum(variables) + locked_daily <= max_daily)

    # Límites por materia/día y días mínimos.
    distribution_penalties = []
    consecutive_penalties = []
    for assignment in assignments:
        aid = int(assignment["id"])
        session_ids = [si for si, s in enumerate(sessions) if int(s["assignment"]["id"]) == aid]
        if not session_ids:
            continue
        day_used = []
        for day in days:
            did = int(day["id"])
            vars_day = [x[(si, sl)] for si in session_ids for sl, (d, _, _, _) in enumerate(slots)
                        if d == did and (si, sl) in x]
            if vars_day:
                model.Add(sum(vars_day) <= max(1, int(assignment.get("max_per_day", 1))))
                used = model.NewBoolVar(f"dayused_{aid}_{did}")
                model.Add(sum(vars_day) >= used)
                model.Add(sum(vars_day) <= len(vars_day) * used)
                day_used.append(used)
                concentration = model.NewIntVar(0, len(vars_day), f"conc_{aid}_{did}")
                model.Add(concentration >= sum(vars_day) - 1)
                distribution_penalties.append(concentration)
        minimum_days = min(int(assignment.get("min_days", 1)), int(assignment.get("required", 1)))
        already_days = len({int(r["day_id"]) for r in locked if int(r.get("assignment_id") or 0) == aid})
        if day_used and minimum_days > already_days:
            model.Add(sum(day_used) >= min(len(day_used), minimum_days - already_days))

        # Ventanas consecutivas.
        max_consecutive = max(1, int(assignment.get("max_consecutive", 2)))
        allow_consecutive = bool(assignment.get("allow_consecutive", False))
        for day in days:
            did = int(day["id"])
            hour_ids = [int(h["id"]) for h in hours]
            for start in range(0, max(0, len(hour_ids) - max_consecutive)):
                window = hour_ids[start:start + max_consecutive + 1]
                vars_window = [x[(si, slot_index[(did, hid)])] for si in session_ids for hid in window
                               if (did, hid) in slot_index and (si, slot_index[(did, hid)]) in x]
                if vars_window:
                    if not allow_consecutive:
                        model.Add(sum(vars_window) <= 1)
                    else:
                        model.Add(sum(vars_window) <= max_consecutive)
                        over = model.NewIntVar(0, len(vars_window), f"consec_{aid}_{did}_{start}")
                        model.Add(over >= sum(vars_window) - max_consecutive)
                        consecutive_penalties.append(over)

    # Preferencias y autorización.
    authorization_vars = []
    preference_penalties = []
    early_penalties = []
    latest_order = max((h["order"] for h in hours), default=1)
    for (si, sl), var in x.items():
        assignment = sessions[si]["assignment"]
        teacher = assignment.get("teacher_id")
        day_id, hour_id, _, hour_order = slots[sl]
        state = avail_state(int(teacher) if teacher else None, day_id, hour_id)
        if state == "autorizacion":
            authorization_vars.append(var)
        if state == "preferido":
            # Reward is modeled as absence of a penalty.
            pass
        elif teacher:
            preference_penalties.append(var)

        pref = assignment.get("preference", "cualquiera")
        if pref == "primeras":
            early_penalties.append((hour_order - 1) * var)
        elif pref == "ultimas":
            early_penalties.append((latest_order - hour_order) * var)

    # Compactación y límites personales del docente.
    gap_vars = []
    teachers = {int(a["teacher_id"]) for a in assignments if a.get("teacher_id")}
    for teacher in teachers:
        cfg = teacher_configs.get(str(teacher), {})
        max_consecutive_teacher = max(1, int(cfg.get("max_consecutive", 3)))
        min_rest = max(0, int(cfg.get("min_rest", 0)))
        for day in days:
            did = int(day["id"])
            occupancy = []
            locked_flags = []
            hour_orders = []
            for h in hours:
                hid = int(h["id"])
                vars_slot = teacher_slot_vars.get((teacher, did, hid), [])
                occ = model.NewBoolVar(f"occ_{teacher}_{did}_{hid}")
                locked_occ = 1 if locked_teacher[(teacher, did, hid)] else 0
                if vars_slot:
                    model.Add(sum(vars_slot) + locked_occ >= occ)
                    model.Add(sum(vars_slot) + locked_occ <= (len(vars_slot) + locked_occ) * occ)
                else:
                    model.Add(occ == locked_occ)
                occupancy.append(occ)
                locked_flags.append(locked_occ)
                hour_orders.append(int(h["order"]))

            # Si los bloques ya fijados incumplen una regla, no se vuelve inviable
            # toda la propuesta: el diagnóstico de Laravel lo mostrará para revisión.
            for start in range(0, max(0, len(occupancy) - max_consecutive_teacher)):
                window = occupancy[start:start + max_consecutive_teacher + 1]
                locked_window = locked_flags[start:start + max_consecutive_teacher + 1]
                if sum(locked_window) <= max_consecutive_teacher:
                    model.Add(sum(window) <= max_consecutive_teacher)

            if min_rest > 0:
                for i in range(len(occupancy)):
                    for j in range(i + 1, len(occupancy)):
                        distance = abs(hour_orders[j] - hour_orders[i])
                        if distance == 0 or distance > min_rest:
                            continue
                        if locked_flags[i] and locked_flags[j]:
                            continue
                        if locked_flags[i]:
                            model.Add(occupancy[j] == 0)
                        elif locked_flags[j]:
                            model.Add(occupancy[i] == 0)
                        else:
                            model.Add(occupancy[i] + occupancy[j] <= 1)

            for i in range(1, len(occupancy) - 1):
                gap = model.NewBoolVar(f"gap_{teacher}_{did}_{i}")
                model.Add(gap <= occupancy[i - 1])
                model.Add(gap <= occupancy[i + 1])
                model.Add(gap + occupancy[i] <= 1)
                model.Add(gap >= occupancy[i - 1] + occupancy[i + 1] - occupancy[i] - 1)
                gap_vars.append(gap)

    weights = {
        "compactar_docente": {"gap": 24, "distribution": 5, "preference": 4, "sim": 10, "early": 1},
        "distribucion_alumnos": {"gap": 5, "distribution": 25, "preference": 4, "sim": 12, "early": 2},
        "preferencias": {"gap": 5, "distribution": 8, "preference": 20, "sim": 14, "early": 8},
        "equilibrio": {"gap": 12, "distribution": 14, "preference": 10, "sim": 16, "early": 4},
    }[objective_name]

    # Los pesos globales configurados en Laravel sí participan en el objetivo.
    gap_weight = round(weights["gap"] * rule_weight("reducir_huecos_docente", 12) / 12)
    distribution_weight = round(weights["distribution"] * rule_weight("distribuir_materia", 10) / 10)
    preference_base = rule_weight("preferencias_horarias", 8) + rule_weight("premiar_bloque_preferido", 6)
    preference_weight = round(weights["preference"] * preference_base / 14) if preference_base else 0
    simultaneous_weight = round(weights["sim"] * rule_weight("penalizar_traslape_excepcional", 18) / 18)
    consecutive_weight = round(weights["distribution"] * rule_weight("limitar_consecutivas", 8) / 8)
    authorization_weight = max(0, rule_weight("disponibilidad_docente", 100)) * 3

    objective = []
    objective.extend(10000 * v for v in unassigned.values())
    objective.extend(authorization_weight * v for v in authorization_vars)
    objective.extend(gap_weight * v for v in gap_vars)
    objective.extend(distribution_weight * v for v in distribution_penalties)
    objective.extend(preference_weight * v for v in preference_penalties)
    objective.extend(simultaneous_weight * v for v in simultaneous_excess)
    objective.extend(max(0, weights["early"] * preference_weight) * term for term in early_penalties)
    objective.extend(consecutive_weight * v for v in consecutive_penalties)
    model.Minimize(sum(objective))

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = seconds
    solver.parameters.num_search_workers = 8
    solver.parameters.random_seed = 20260729
    status = solver.Solve(model)

    assigned = []
    unassigned_rows = []
    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        for si, session in enumerate(sessions):
            found = False
            for sl, (day_id, hour_id, _, _) in enumerate(slots):
                var = x.get((si, sl))
                if var is not None and solver.Value(var):
                    a = session["assignment"]
                    assigned.append({
                        "assignment_id": int(a["id"]),
                        "group_id": int(a["group_id"]),
                        "teacher_id": int(a["teacher_id"]) if a.get("teacher_id") else None,
                        "day_id": day_id,
                        "hour_id": hour_id,
                    })
                    found = True
                    break
            if not found:
                unassigned_rows.append({"assignment_id": int(session["assignment"]["id"]), "session": session["key"]})
    else:
        unassigned_rows = [{"assignment_id": int(s["assignment"]["id"]), "session": s["key"]} for s in sessions]

    return {
        "objective": objective_name,
        "status": solver.StatusName(status),
        "assigned": assigned,
        "unassigned": unassigned_rows,
        "objective_value": solver.ObjectiveValue() if status in (cp_model.OPTIMAL, cp_model.FEASIBLE) else None,
        "wall_time": solver.WallTime(),
    }


def main() -> int:
    if len(sys.argv) != 2:
        print(json.dumps({"ok": False, "error": "Se requiere la ruta del JSON de entrada."}, ensure_ascii=False))
        return 1
    payload = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
    objectives = payload.get("objectives") or ["equilibrio"]
    proposals = [solve(payload, obj) for obj in objectives]
    print(json.dumps({"ok": True, "engine": "ortools-cp-sat", "proposals": proposals}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

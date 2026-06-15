<style>
    @page {
        margin: 18px 38px 28px 38px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        font-size: 11px;
        color: #000;
    }

    .page {
        width: 100%;
        position: relative;
    }

    .top-header {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }

    .logo-cell {
        width: 68%;
        vertical-align: top;
    }

    .mascota-cell {
        width: 32%;
        text-align: right;
        vertical-align: top;
    }

    .logo-principal {
        width: 245px;
        max-height: 76px;
        object-fit: contain;
    }

    .mascota {
        width: 58px;
        max-height: 70px;
        object-fit: contain;
    }

    .datos-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        border: 1px solid #000;
        margin-top: 4px;
    }

    .datos-table td {
        border: 1px solid #000;
        padding: 4px 5px;
        vertical-align: middle;
        height: 22px;
    }

    .label {
        font-weight: normal;
    }

    .dato {
        font-weight: bold;
        text-transform: uppercase;
    }

    .pink {
        background: #efc2ef;
    }

    .yellow {
        background: #fff200;
    }

    .green {
        background: #92d050;
    }

    .main-table-wrapper {
        position: relative;
        margin-top: 16px;
    }

    .watermark {
        position: absolute;
        top: 34px;
        left: 116px;
        width: 420px;
        opacity: 0.07;
        z-index: 0;
    }

    .ficha-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        border: 1px solid #000;
        position: relative;
        z-index: 2;
    }

    .ficha-table th,
    .ficha-table td {
        border: 1px solid #000;
    }

    .ficha-table th {
        background: #efc2ef;
        text-align: center;
        font-size: 12px;
        height: 22px;
        line-height: 22px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .campo-col {
        width: 132px;
        text-align: center;
        vertical-align: middle;
        padding: 2px;
        background: rgba(255, 255, 255, 0.92);
    }

    .descripcion-col {
        vertical-align: top;
        padding: 7px 9px;
        height: 94px;
        text-align: justify;
        line-height: 1.35;
        background: rgba(255, 255, 255, 0.70);
    }

    .campo-img {
        width: 126px;
        max-height: 84px;
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }

    .campo-fallback {
        width: 126px;
        height: 82px;
        border: 1px solid #c8c8c8;
        padding: 8px 5px;
        text-align: center;
        font-weight: bold;
        font-size: 10px;
        line-height: 1.15;
        color: #006492;
        background: #fff;
    }

    /*
    |--------------------------------------------------------------------------
    | Contenido HTML generado por TinyMCE
    |--------------------------------------------------------------------------
    */

    .texto-ficha {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        font-size: 11px;
        line-height: 1.35;
        text-align: justify;
        color: #000;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .texto-ficha p {
        margin: 0 0 5px 0;
        padding: 0;
    }

    .texto-ficha p:last-child {
        margin-bottom: 0;
    }

    .texto-ficha strong,
    .texto-ficha b {
        font-weight: bold;
    }

    .texto-ficha em,
    .texto-ficha i {
        font-style: italic;
    }

    .texto-ficha u {
        text-decoration: underline;
    }

    .texto-ficha s,
    .texto-ficha strike {
        text-decoration: line-through;
    }

    .texto-ficha span {
        line-height: 1.35;
    }

    .texto-ficha div {
        margin: 0;
        padding: 0;
    }

    .texto-ficha br {
        line-height: 1.35;
    }

    .texto-ficha ul,
    .texto-ficha ol {
        margin: 4px 0 6px 17px;
        padding: 0;
    }

    .texto-ficha li {
        margin: 0 0 3px 0;
        padding: 0;
    }

    .texto-ficha h1,
    .texto-ficha h2,
    .texto-ficha h3,
    .texto-ficha h4,
    .texto-ficha h5,
    .texto-ficha h6 {
        margin: 3px 0 5px 0;
        padding: 0;
        font-weight: bold;
        line-height: 1.2;
    }

    .texto-ficha h1 {
        font-size: 15px;
    }

    .texto-ficha h2 {
        font-size: 14px;
    }

    .texto-ficha h3 {
        font-size: 13px;
    }

    .texto-ficha h4,
    .texto-ficha h5,
    .texto-ficha h6 {
        font-size: 12px;
    }

    .texto-ficha table {
        width: 100%;
        border-collapse: collapse;
        margin: 5px 0;
    }

    .texto-ficha table,
    .texto-ficha th,
    .texto-ficha td {
        border: 1px solid #333;
    }

    .texto-ficha th,
    .texto-ficha td {
        padding: 3px 4px;
        vertical-align: top;
    }

    .texto-ficha th {
        font-weight: bold;
        background: #f3f3f3;
    }

    .texto-ficha a {
        color: #000;
        text-decoration: underline;
    }

    .texto-ficha blockquote {
        margin: 4px 0 6px 8px;
        padding-left: 8px;
        border-left: 2px solid #999;
    }

    .recomendaciones-title {
        background: #efc2ef;
        text-align: center;
        font-size: 15px;
        font-weight: normal;
        height: 22px;
        line-height: 22px;
    }

    .recomendaciones-body {
        min-height: 44px;
        padding: 7px 9px;
        text-align: justify;
        line-height: 1.35;
        background: rgba(255, 255, 255, 0.70);
    }

    .firmas {
        width: 100%;
        border-collapse: collapse;
        margin-top: 74px;
    }

    .firmas td {
        width: 50%;
        text-align: center;
        vertical-align: top;
        font-size: 11px;
    }

    .linea {
        width: 230px;
        border-top: 1px solid #1f2a44;
        margin: 0 auto 4px auto;
        height: 1px;
    }

    .firma-nombre {
        font-size: 11px;
        text-transform: uppercase;
    }

    .firma-cargo {
        font-size: 10px;
        text-transform: uppercase;
    }

    .footer {
        position: fixed;
        left: 38px;
        right: 38px;
        bottom: 12px;
        border-top: 1px solid #c7d5e6;
        padding-top: 5px;
        text-align: center;
        font-size: 7px;
        color: #57708d;
        text-transform: uppercase;
        line-height: 1.2;
    }
</style>

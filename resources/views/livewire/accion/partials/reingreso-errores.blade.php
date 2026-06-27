<section class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-200">
    @foreach ($errors->all() as $error)
        <p>• {{ $error }}</p>
    @endforeach
</section>

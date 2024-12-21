<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Something went wrong</title>
    {{ css_entry_tag('main.css') }}
</head>
<body class="h-full ">
<main class="grid min-h-full place-items-center bg-white px-6 py-24 sm:py-32 lg:px-8 bg-gray-50 dark:bg-gray-800">
    <div class="text-center">
        <p class="text-base font-semibold text-indigo-600">500</p>
        <h1 class="mt-4 text-balance text-5xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 sm:text-7xl">Something went wrong</h1>
        <p class="mt-6 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8">Sorry, an error occurred.</p>
        <div class="mt-10 flex items-center justify-center gap-x-6">
            <a href="/" class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Go back home</a>
        </div>
        <div class="mt-6">
            <div class="block p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-500 dark:border-gray-700">
                <h5 class="mb-2 text-left text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Stack trace:</h5>
                <pre class="text-left font-mono text-gray-900 dark:text-white"><code>{{ exception }}</code></pre>
            </div>
        </div>
    </div>
</main>
</body>
</html>
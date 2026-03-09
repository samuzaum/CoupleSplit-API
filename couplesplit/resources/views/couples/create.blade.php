<x-app-layout>

<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-xl">

<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
shadow-sm
p-10
transition-colors
">

<h1 class="
text-3xl font-bold
text-black dark:text-white
mb-6
">
Criar casal
</h1>

<form method="POST" action="{{ route('couples.store') }}" class="space-y-6">

@csrf

<div>

<label
for="couple_name"
class="
block text-sm
font-medium
text-gray-700 dark:text-gray-300
mb-2
"
>

Nome do casal (opcional)

</label>

<input
type="text"
name="name"
id="couple_name"
placeholder="Ex: Samuel & Evelyn"

class="
w-full
px-4 py-3
rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black
text-black dark:text-white
focus:outline-none
focus:ring-2
focus:ring-black dark:focus:ring-white
"
/>

</div>


<button
type="submit"

class="
w-full
px-6 py-3
rounded-full
font-semibold
bg-black text-white
dark:bg-white dark:text-black
hover:opacity-90
transition
"
>

Criar casal

</button>

</form>

</div>

</div>

</div>

</x-app-layout>
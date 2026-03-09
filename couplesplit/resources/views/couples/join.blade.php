<x-app-layout>
<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-xl">

<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
shadow-sm
p-10
">

<h1 class="text-3xl font-bold text-black dark:text-white mb-4">
Entrar em um casal
</h1>

<p class="text-gray-600 dark:text-gray-400 mb-8">
Digite o código de convite enviado pelo seu parceiro.
</p>

<form method="POST" action="{{ route('couple.join') }}">
@csrf

<input
type="text"
name="token"
placeholder="Código de convite"
required
class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 mb-6"
/>

<button
type="submit"
class="
w-full
px-6 py-3
rounded-full
font-semibold
bg-black text-white
dark:bg-white dark:text-black
"
>
Entrar no casal
</button>

</form>

</div>

</div>

</div>
</x-app-layout>
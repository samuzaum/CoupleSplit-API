<x-app-layout>

<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-xl">

<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
shadow-sm
p-10
text-center
">

<h1 class="text-3xl font-bold text-black dark:text-white mb-6">
Casal criado com sucesso
</h1>

<p class="text-gray-600 dark:text-gray-400 mb-6">
Envie este código para seu parceiro entrar no casal.
</p>

<div class="
text-4xl
font-bold
tracking-widest
bg-gray-100 dark:bg-gray-900
py-6
rounded-xl
mb-8
">
{{ $token }}
</div>

<a href="{{ route('dashboard') }}"
class="
inline-block
px-6 py-3
rounded-full
font-semibold
bg-black text-white
dark:bg-white dark:text-black
hover:opacity-90
transition
">
Ir para o dashboard
</a>

</div>

</div>

</div>

</x-app-layout>
<nav x-data="{ open: false }"
class="bg-white dark:bg-black border-b border-gray-200 dark:border-gray-800">

<div class="max-w-6xl mx-auto px-6">

<div class="flex justify-between h-16 items-center">

{{-- LOGO --}}
<div class="flex items-center gap-10">

<a href="{{ route('dashboard') }}" class="flex items-center">

<x-application-logo
class="h-8 w-auto text-black dark:text-white"
/>

</a>


{{-- MENU DESKTOP --}}
<div class="hidden sm:flex items-center gap-8 text-sm font-medium">

<a href="{{ route('dashboard') }}"
class="
{{ request()->routeIs('dashboard')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Dashboard

</a>

<a href="{{ route('expenses.index') }}"
class="
{{ request()->routeIs('expenses.*')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Despesas

</a>

<a href="{{ route('payments.create') }}"
class="
{{ request()->routeIs('payments.*')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Pagamentos

</a>

<a href="{{ route('cards.index') }}"
class="
{{ request()->routeIs('cards.*')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Cartões

</a>

</div>

</div>



{{-- USER MENU --}}
<div class="hidden sm:flex items-center">

<x-dropdown align="right" width="48">

<x-slot name="trigger">

<button class="
flex items-center gap-2
text-sm
text-gray-600 dark:text-gray-300
hover:text-black dark:hover:text-white
">

{{ Auth::user()->name }}

<svg class="h-4 w-4 fill-current"
xmlns="http://www.w3.org/2000/svg"
viewBox="0 0 20 20">

<path fill-rule="evenodd"
d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
clip-rule="evenodd"/>

</svg>

</button>

</x-slot>


<x-slot name="content">

<x-dropdown-link :href="route('profile.edit')">
Perfil
</x-dropdown-link>

<form method="POST" action="{{ route('logout') }}">
@csrf

<x-dropdown-link
:href="route('logout')"
onclick="event.preventDefault(); this.closest('form').submit();">

Sair

</x-dropdown-link>

</form>

</x-slot>

</x-dropdown>

</div>



{{-- BOTÃO MOBILE --}}
<div class="sm:hidden">

<button
@click="open = ! open"
class="p-2 rounded-md text-gray-500 hover:text-black dark:hover:text-white">

<svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">

<path
:class="{'hidden': open, 'inline-flex': ! open }"
class="inline-flex"
stroke-linecap="round"
stroke-linejoin="round"
stroke-width="2"
d="M4 6h16M4 12h16M4 18h16"/>

<path
:class="{'hidden': ! open, 'inline-flex': open }"
class="hidden"
stroke-linecap="round"
stroke-linejoin="round"
stroke-width="2"
d="M6 18L18 6M6 6l12 12"/>

</svg>

</button>

</div>

</div>

</div>



{{-- MENU MOBILE --}}
<div
:class="{'block': open, 'hidden': ! open}"
class="sm:hidden border-t border-gray-200 dark:border-gray-800">

<div class="px-6 py-4 space-y-3 text-sm">

<a href="{{ route('dashboard') }}" class="block">
Dashboard
</a>

<a href="{{ route('expenses.index') }}" class="block">
Despesas
</a>

<a href="{{ route('payments.create') }}" class="block">
Pagamentos
</a>

<a href="{{ route('cards.index') }}" class="block">
Cartões
</a>

</div>


<div class="border-t border-gray-200 dark:border-gray-800 px-6 py-4">

<p class="text-sm text-gray-500">
{{ Auth::user()->name }}
</p>

<p class="text-xs text-gray-400">
{{ Auth::user()->email }}
</p>

<form method="POST" action="{{ route('logout') }}" class="mt-3">
@csrf

<button class="text-sm text-red-500">
Sair
</button>

</form>

</div>

</div>

</nav>
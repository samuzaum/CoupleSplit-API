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
{{ request()->routeIs('payments.create')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Pagamentos

</a>

<a href="{{ route('payments.history') }}"
class="
{{ request()->routeIs('payments.history')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Histórico

</a>

<a href="{{ route('cards.index') }}"
class="
{{ request()->routeIs('cards.*')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Cartões

</a>

<a href="{{ route('calendar.index') }}"
class="
{{ request()->routeIs('calendar.*')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Calendário

</a>

<a href="{{ route('summary.index') }}"
class="
{{ request()->routeIs('summary.*')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Resumo

</a>

<a href="{{ route('calculator.index') }}"
class="
{{ request()->routeIs('calculator.*')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Simulador

</a>

<a href="{{ route('notifications.index') }}"
class="relative
{{ request()->routeIs('notifications.*')
? 'text-black dark:text-white'
: 'text-gray-500 hover:text-black dark:hover:text-white' }}
">

Notificações

@if ($navNotificationCount > 0)
<span class="absolute -top-2 -right-3 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
{{ $navNotificationCount }}
</span>
@endif

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



{{-- BOTÃO MOBILE (mais páginas) --}}
<div class="sm:hidden flex items-center gap-3">

@if ($navNotificationCount > 0)
<a href="{{ route('notifications.index') }}" class="relative p-1">
    <span class="bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ $navNotificationCount }}</span>
</a>
@endif

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



{{-- MENU MOBILE (páginas secundárias) --}}
<div
:class="{'block': open, 'hidden': ! open}"
class="sm:hidden border-t border-gray-200 dark:border-gray-800">

<div class="px-6 py-4 space-y-3 text-sm">

<a href="{{ route('cards.index') }}" class="block py-1 {{ request()->routeIs('cards.*') ? 'font-semibold text-black dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
Cartões
</a>

<a href="{{ route('calendar.index') }}" class="block py-1 {{ request()->routeIs('calendar.*') ? 'font-semibold text-black dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
Calendário
</a>

<a href="{{ route('summary.index') }}" class="block py-1 {{ request()->routeIs('summary.*') ? 'font-semibold text-black dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
Resumo
</a>

<a href="{{ route('calculator.index') }}" class="block py-1 {{ request()->routeIs('calculator.*') ? 'font-semibold text-black dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
Simulador
</a>

<a href="{{ route('goals.index') }}" class="block py-1 {{ request()->routeIs('goals.*') ? 'font-semibold text-black dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
Metas
</a>

<a href="{{ route('activity.index') }}" class="block py-1 {{ request()->routeIs('activity.*') ? 'font-semibold text-black dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
Atividade
</a>

<a href="{{ route('couple.show') }}" class="block py-1 {{ request()->routeIs('couple.*') ? 'font-semibold text-black dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
Casal
</a>

</div>

<div class="border-t border-gray-200 dark:border-gray-800 px-6 py-4">

<p class="text-sm text-gray-600 dark:text-gray-400">{{ Auth::user()->name }}</p>
<p class="text-xs text-gray-400">{{ Auth::user()->email }}</p>

<div class="flex gap-4 mt-3">
    <a href="{{ route('profile.edit') }}" class="text-sm text-gray-500">Perfil</a>
    <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button class="text-sm text-red-500">Sair</button>
    </form>
</div>

</div>

</div>

</nav>

{{-- BARRA DE NAVEGAÇÃO INFERIOR (mobile only) --}}
<nav class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-black border-t border-gray-200 dark:border-gray-800">
<div class="flex items-center justify-around h-16 px-1">

    {{-- Dashboard --}}
    <a href="{{ route('dashboard') }}"
        class="flex flex-col items-center gap-0.5 px-3 py-2 {{ request()->routeIs('dashboard') ? 'text-black dark:text-white' : 'text-gray-400' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
        </svg>
        <span class="text-[10px] font-medium leading-none">Início</span>
    </a>

    {{-- Despesas --}}
    <a href="{{ route('expenses.index') }}"
        class="flex flex-col items-center gap-0.5 px-3 py-2 {{ request()->routeIs('expenses.*') ? 'text-black dark:text-white' : 'text-gray-400' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
        </svg>
        <span class="text-[10px] font-medium leading-none">Despesas</span>
    </a>

    {{-- FAB Nova despesa --}}
    <a href="{{ route('expenses.create') }}"
        class="-mt-5 flex items-center justify-center w-14 h-14 rounded-full bg-black dark:bg-white shadow-lg">
        <svg class="w-7 h-7 text-white dark:text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
    </a>

    {{-- Histórico --}}
    <a href="{{ route('payments.history') }}"
        class="flex flex-col items-center gap-0.5 px-3 py-2 {{ request()->routeIs('payments.*') ? 'text-black dark:text-white' : 'text-gray-400' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 21Z"/>
        </svg>
        <span class="text-[10px] font-medium leading-none">Pagamentos</span>
    </a>

    {{-- Notificações --}}
    <a href="{{ route('notifications.index') }}"
        class="relative flex flex-col items-center gap-0.5 px-3 py-2 {{ request()->routeIs('notifications.*') ? 'text-black dark:text-white' : 'text-gray-400' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
        </svg>
        @if ($navNotificationCount > 0)
        <span class="absolute top-1.5 right-1.5 bg-red-500 text-white text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center">
            {{ $navNotificationCount }}
        </span>
        @endif
        <span class="text-[10px] font-medium leading-none">Alertas</span>
    </a>

</div>
</nav>
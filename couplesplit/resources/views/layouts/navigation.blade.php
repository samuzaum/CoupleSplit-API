<nav x-data="{ open: false }"
class="bg-white dark:bg-black border-b border-gray-200 dark:border-gray-800">

<div class="max-w-6xl mx-auto px-6">
<div class="flex justify-between h-16 items-center gap-6">

{{-- LOGO --}}
<a href="{{ route('dashboard') }}" class="flex items-center shrink-0">
    <img id="nav-logo" src="{{ asset('images/logo-preta.png') }}" alt="CoupleSplit" class="h-9 w-auto">
</a>

{{-- MENU DESKTOP --}}
<div class="hidden sm:flex items-center gap-6 text-sm font-medium flex-1">

    @php
    $navActive = 'text-black dark:text-white';
    $navInactive = 'text-gray-500 hover:text-black dark:hover:text-white';
    $isSecondary = request()->routeIs(['cards.*','calendar.*','summary.*','calculator.*','goals.*','activity.*','couple.*','installments.*','expenses.recurring']);
    @endphp

    <a href="{{ route('dashboard') }}"       class="{{ request()->routeIs('dashboard') ? $navActive : $navInactive }}">Dashboard</a>
    <a href="{{ route('expenses.index') }}"  class="{{ request()->routeIs('expenses.*') && !$isSecondary ? $navActive : $navInactive }}">Despesas</a>
    <a href="{{ route('payments.create') }}" class="{{ request()->routeIs('payments.create') ? $navActive : $navInactive }}">Pagar</a>
    <a href="{{ route('payments.history') }}"class="{{ request()->routeIs('payments.history') ? $navActive : $navInactive }}">Histórico</a>

    {{-- Links extras — visíveis só em telas grandes (xl+) --}}
    <a href="{{ route('cards.index') }}"        class="hidden xl:block {{ request()->routeIs('cards.*') ? $navActive : $navInactive }}">Cartões</a>
    <a href="{{ route('installments.index') }}" class="hidden xl:block {{ request()->routeIs('installments.*') ? $navActive : $navInactive }}">Parcelas</a>
    <a href="{{ route('goals.index') }}"        class="hidden xl:block {{ request()->routeIs('goals.*') ? $navActive : $navInactive }}">Metas</a>
    <a href="{{ route('calendar.index') }}"     class="hidden xl:block {{ request()->routeIs('calendar.*') ? $navActive : $navInactive }}">Calendário</a>
    <a href="{{ route('summary.index') }}"      class="hidden xl:block {{ request()->routeIs('summary.*') ? $navActive : $navInactive }}">Resumo</a>
    <a href="{{ route('calculator.index') }}"   class="hidden xl:block {{ request()->routeIs('calculator.*') ? $navActive : $navInactive }}">Simulador</a>

    {{-- MAIS — só aparece abaixo de xl --}}
    <div class="xl:hidden relative" x-data="{ moreOpen: false }" @click.outside="moreOpen = false">
        <button @click="moreOpen = !moreOpen"
            class="flex items-center gap-1 {{ $isSecondary ? $navActive : $navInactive }}">
            Mais
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>
        </button>
        <div x-show="moreOpen" x-transition
            class="absolute left-0 top-8 z-50 w-44 bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl shadow-lg py-2 text-sm">
            <a href="{{ route('cards.index') }}"        class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900">Cartões</a>
            <a href="{{ route('installments.index') }}" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900">Parcelas</a>
            <a href="{{ route('expenses.recurring') }}" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900">Recorrentes</a>
            <a href="{{ route('goals.index') }}"        class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900">Metas</a>
            <a href="{{ route('calendar.index') }}"     class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900">Calendário</a>
            <a href="{{ route('summary.index') }}"      class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900">Resumo</a>
            <a href="{{ route('calculator.index') }}"   class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900">Simulador</a>
            <a href="{{ route('activity.index') }}"     class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900">Atividade</a>
            <a href="{{ route('couple.show') }}"        class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900">Casal</a>
        </div>
    </div>

</div>

{{-- ÍCONES DIREITA --}}
<div class="hidden sm:flex items-center gap-2">

    {{-- Notificações --}}
    <a href="{{ route('notifications.index') }}"
        class="relative w-8 h-8 rounded-full flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors
        {{ request()->routeIs('notifications.*') ? 'text-black dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
        </svg>
        @if ($navNotificationCount > 0)
        <span class="absolute top-0.5 right-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full w-3.5 h-3.5 flex items-center justify-center">
            {{ $navNotificationCount > 9 ? '9+' : $navNotificationCount }}
        </span>
        @endif
    </a>

    {{-- Tema --}}
    <button onclick="toggleTheme()" title="Alternar tema"
        class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
        <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
        </svg>
        <svg class="w-4 h-4 dark:hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
        </svg>
    </button>

    {{-- Perfil --}}
    <x-dropdown align="right" width="48">
        <x-slot name="trigger">
            <button class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition-colors">
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                </svg>
            </button>
        </x-slot>
        <x-slot name="content">
            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-800">
                <p class="text-xs font-medium text-black dark:text-white">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
            </div>
            <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>
            <x-dropdown-link :href="route('couple.show')">Casal</x-dropdown-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    Sair
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>

</div>

{{-- MOBILE: tema + hamburger --}}
<div class="sm:hidden flex items-center gap-2">
    <button onclick="toggleTheme()"
        class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
        <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
        </svg>
        <svg class="w-4 h-4 dark:hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
        </svg>
    </button>
    <button @click="open = !open" class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white">
        <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

</div>
</div>

{{-- MENU MOBILE --}}
<div :class="{'block': open, 'hidden': !open}" class="sm:hidden border-t border-gray-200 dark:border-gray-800">
<div class="px-6 py-3 space-y-1 text-sm">
    @php
    $mobileLinks = [
        ['route' => 'cards.index',          'label' => 'Cartões'],
        ['route' => 'installments.index',   'label' => 'Parcelas'],
        ['route' => 'expenses.recurring',   'label' => 'Recorrentes'],
        ['route' => 'goals.index',          'label' => 'Metas'],
        ['route' => 'calendar.index',       'label' => 'Calendário'],
        ['route' => 'summary.index',        'label' => 'Resumo'],
        ['route' => 'calculator.index',     'label' => 'Simulador'],
        ['route' => 'activity.index',       'label' => 'Atividade'],
        ['route' => 'couple.show',          'label' => 'Casal'],
    ];
    @endphp
    @foreach ($mobileLinks as $link)
    <a href="{{ route($link['route']) }}"
        class="block py-2 px-2 rounded-lg {{ request()->routeIs(rtrim($link['route'], '.index') . '*') ? 'bg-gray-100 dark:bg-gray-900 font-semibold text-black dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
        {{ $link['label'] }}
    </a>
    @endforeach
</div>
<div class="border-t border-gray-200 dark:border-gray-800 px-6 py-4">
    <p class="text-sm font-medium text-black dark:text-white">{{ Auth::user()->name }}</p>
    <p class="text-xs text-gray-400 mb-3">{{ Auth::user()->email }}</p>
    <div class="flex gap-4">
        <a href="{{ route('profile.edit') }}" class="text-sm text-gray-500 dark:text-gray-400">Perfil</a>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="text-sm text-red-500">Sair</button>
        </form>
    </div>
</div>
</div>

</nav>

{{-- BARRA INFERIOR MOBILE --}}
<nav class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-black border-t border-gray-200 dark:border-gray-800">
<div class="flex items-center justify-around h-16 px-1">

    <a href="{{ route('dashboard') }}"
        class="flex flex-col items-center gap-0.5 px-3 py-2 {{ request()->routeIs('dashboard') ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-600' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
        </svg>
        <span class="text-[10px] font-medium leading-none">Início</span>
    </a>

    <a href="{{ route('expenses.index') }}"
        class="flex flex-col items-center gap-0.5 px-3 py-2 {{ request()->routeIs('expenses.*') ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-600' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
        </svg>
        <span class="text-[10px] font-medium leading-none">Despesas</span>
    </a>

    <a href="{{ route('expenses.create') }}"
        class="-mt-5 flex items-center justify-center w-14 h-14 rounded-full bg-black dark:bg-white shadow-lg">
        <svg class="w-7 h-7 text-white dark:text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
    </a>

    <a href="{{ route('payments.history') }}"
        class="flex flex-col items-center gap-0.5 px-3 py-2 {{ request()->routeIs('payments.*') ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-600' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 21Z"/>
        </svg>
        <span class="text-[10px] font-medium leading-none">Pagamentos</span>
    </a>

    <a href="{{ route('notifications.index') }}"
        class="relative flex flex-col items-center gap-0.5 px-3 py-2 {{ request()->routeIs('notifications.*') ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-600' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
        </svg>
        @if ($navNotificationCount > 0)
        <span class="absolute top-1.5 right-2 bg-red-500 text-white text-[9px] font-bold rounded-full w-3.5 h-3.5 flex items-center justify-center">
            {{ $navNotificationCount }}
        </span>
        @endif
        <span class="text-[10px] font-medium leading-none">Alertas</span>
    </a>

</div>
</nav>

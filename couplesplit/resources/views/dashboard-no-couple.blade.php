<x-app-layout>
    <div class="mt-12 pb-20 px-6">

        <div class="mx-auto max-w-xl">

            <div class="
                bg-white dark:bg-black
                border border-gray-200 dark:border-gray-800
                rounded-3xl
                shadow-sm
                p-10
                transition-colors duration-200
            ">

                <h1 class="
                    text-3xl font-bold
                    text-black dark:text-white
                    mb-4
                ">
                    Você ainda não faz parte de um casal
                </h1>

                <p class="
                    text-gray-600 dark:text-gray-400
                    text-base leading-relaxed
                    mb-8
                ">
                    Para começar a organizar despesas e acompanhar saldos,
                    é necessário criar um casal ou entrar com um código de convite.
                </p>

                <div class="space-y-4">

                    <a href="{{ route('couples.create') }}"
                       class="
                           block text-center
                           px-6 py-3
                           rounded-full
                           font-semibold
                           bg-black text-white
                           dark:bg-white dark:text-black
                           hover:opacity-90
                           transition
                       ">
                        Criar casal
                    </a>

                    <a href="{{ route('couple.join.form') }}"
                       class="
                           block text-center
                           px-6 py-3
                           rounded-full
                           font-semibold
                           border border-gray-300 dark:border-gray-700
                           text-black dark:text-white
                           hover:bg-gray-100 dark:hover:bg-gray-900
                           transition
                       ">
                        Entrar com código
                    </a>

                </div>

            </div>

        </div>

    </div>
</x-app-layout>
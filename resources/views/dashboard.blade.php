<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">
                        Accesso effettuato ✅
                    </h3>

                    <p class="mt-2">
                        Il tuo account è stato creato correttamente, ma al momento <span class="font-semibold">non hai ancora alcun ruolo assegnato</span>.
                    </p>

                    <div class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-900">
                        <p class="font-semibold">In attesa di attivazione</p>
                        <p class="mt-1">
                            Per poter utilizzare l’applicazione, dovrai attendere che l’amministrazione abiliti il tuo profilo e ti assegni i permessi necessari.
                        </p>
                    </div>

                    <p class="mt-4 text-sm text-gray-600">
                        Se pensi che ci sia un errore o l’attivazione tarda ad arrivare, contatta l’amministratore di sistema.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

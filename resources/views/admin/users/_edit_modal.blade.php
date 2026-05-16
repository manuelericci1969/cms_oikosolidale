<div class="modal fade" id="editModal{{ $user->id }}" tabindex="-1"
     aria-labelledby="editModalLabel{{ $user->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PATCH')

                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel{{ $user->id }}">Modifica utente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Telefono</label>
                        <input type="text"
                               name="phone"
                               class="form-control"
                               value="{{ old('phone', $user->phone) }}"
                               placeholder="+393331234567">
                        <div class="form-text">Inserisci il numero completo, preferibilmente con prefisso internazionale.</div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="whatsapp_opt_in" value="0">
                        <input class="form-check-input"
                               type="checkbox"
                               id="whatsapp_opt_in_{{ $user->id }}"
                               name="whatsapp_opt_in"
                               value="1"
                            @checked(old('whatsapp_opt_in', $user->whatsapp_opt_in))>
                        <label class="form-check-label" for="whatsapp_opt_in_{{ $user->id }}">
                            Consenso invio comunicazioni WhatsApp
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ruolo</label>
                        <select name="role" class="form-select" required>
                            @foreach(\App\Enums\Role::cases() as $r)
                                <option value="{{ $r->value }}"
                                    @selected(($user->role instanceof \App\Enums\Role ? $user->role->value : $user->role) === $r->value)>
                                    {{ $r->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox"
                               id="is_active_{{ $user->id }}"
                               name="is_active" value="1"
                            @checked($user->is_active)>
                        <label class="form-check-label" for="is_active_{{ $user->id }}">Attivo</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="page-header">
    <div class="header-container">
        <h1>Compartilhar Agenda</h1>
        <a href="<?= PUBLIC_URL ?>/agendas" class="btn btn-secondary">Voltar para Agendas</a>
    </div>
    
    <div class="agenda-meta">
        <h2><?= htmlspecialchars($agenda['title']) ?></h2>
        <span class="badge <?= $agenda['is_public'] ? 'badge-success' : 'badge-secondary' ?>">
            <?= $agenda['is_public'] ? 'Agenda Pública' : 'Agenda Privada' ?>
        </span>
    </div>
</div>

<div class="content-container">
    <!-- Configuração de visibilidade pública -->
    <div class="card">
        <div class="card-header">
            <h3>Visibilidade da Agenda</h3>
        </div>
        <div class="card-body">
            <p>Status atual: <strong><?= $agenda['is_public'] ? 'Agenda Pública' : 'Agenda Privada' ?></strong></p>
            
            <!-- Formulário para tornar pública/privada -->
            <form action="<?= PUBLIC_URL ?>/shares/toggle-public" method="post" class="mt-3">
                <input type="hidden" name="agenda_id" value="<?= $agenda['id'] ?>">
                
                <?php if ($agenda['is_public']): ?>
                    <button type="submit" class="btn btn-danger">Tornar Privada</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-success">Tornar Pública</button>
                <?php endif; ?>
            </form>
            
            <!-- Exibir URL pública se a agenda for pública e tiver hash -->
            <?php if ($agenda['is_public'] && !empty($agenda['public_hash'])): ?>
                <div class="public-url-container mt-3">
                    <p>URL Pública:</p>
                    <div class="input-group">
                        <input type="text" value="<?= BASE_URL ?>/public-agenda/<?= $agenda['public_hash'] ?>" class="form-control" id="publicUrl" readonly>
                        <button class="btn btn-primary" onclick="copyToClipboard('publicUrl')">Copiar</button>
                    </div>
                    <small class="form-text">Esta URL pode ser compartilhada com qualquer pessoa para visualização da agenda.</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Compartilhamento com usuários -->
    <div class="card mt-4">
        <div class="card-header">
            <h3>Compartilhar com Usuários</h3>
        </div>
        <div class="card-body">
            <form action="<?= PUBLIC_URL ?>/shares/add" method="post" class="share-form">
                <input type="hidden" name="agenda_id" value="<?= $agenda['id'] ?>">
                
                <div class="form-row">
                    <div class="form-group form-group-large">
                        <label for="username">e-mail(@ufpr.br) do usuário</label>
                        <input type="text" id="username" name="username" required class="form-control" placeholder="Digite o nome de usuário para compartilhar">
                    </div>
                    
                    <div class="form-group form-group-small">
                        <label class="checkbox-container">
                            <input type="checkbox" name="can_edit" value="1"> 
                            <span class="checkmark"></span>
                            Pode Editar
                        </label>
                    </div>
                    
                    <div class="form-group form-group-small">
                        <button type="submit" class="btn btn-primary">Compartilhar</button>
                    </div>
                </div>
            </form>
            
            <div class="mt-4">
                <h4>Usuários com Acesso</h4>
                
                <?php if (empty($shares)): ?>
                    <div class="empty-state">
                        <p>Esta agenda ainda não foi compartilhada com nenhum usuário.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Permissão</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shares as $share): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($share['username']) ?></td>
                                        <td><?= htmlspecialchars($share['name']) ?></td>
                                        <td><?= htmlspecialchars($share['email']) ?></td>
                                        <td>
                                            <form action="<?= PUBLIC_URL ?>/shares/update-permission" method="post" class="permission-form">
                                                <input type="hidden" name="agenda_id" value="<?= $agenda['id'] ?>">
                                                <input type="hidden" name="user_id" value="<?= $share['user_id'] ?>">
                                                
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="can_edit" onchange="this.form.submit()" <?= $share['can_edit'] ? 'checked' : '' ?>>
                                                    <span class="toggle-slider"></span>
                                                    <span class="toggle-label"><?= $share['can_edit'] ? 'Pode Editar' : 'Apenas Ver' ?></span>
                                                </label>
                                            </form>
                                        </td>   
                                        <td>
                                            <div class="action-buttons-group">
                                                <!-- Botão Enviar E-mail -->
                                                <form action="<?= PUBLIC_URL ?>/shares/send-email" method="post" class="d-inline" title="Enviar e-mail de notificação sobre o compartilhamento">
                                                    <input type="hidden" name="agenda_id" value="<?= $agenda['id'] ?>">
                                                    <input type="hidden" name="user_id" value="<?= $share['user_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-info btn-email">
                                                        <i class="fas fa-envelope"></i> Enviar E-mail
                                                    </button>
                                                </form>
                                                
                                                <!-- Botão Remover -->
                                                <form action="<?= PUBLIC_URL ?>/shares/remove" method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover o compartilhamento com este usuário?')">
                                                    <input type="hidden" name="agenda_id" value="<?= $agenda['id'] ?>">
                                                    <input type="hidden" name="user_id" value="<?= $share['user_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Remover
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?= PUBLIC_URL ?>/app/assets/js/shares/index.js"></script>
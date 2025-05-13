<div class="container">
    <div class="hero-section">
        <h1>Bem-vindo ao Sistema de Agendamento UFPR</h1>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>/login" class="btn btn-primary">Entrar</a>
            </div>
        <?php else: ?>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>/agendas" class="btn btn-primary">Minhas Agendas</a>
                <a href="<?= BASE_URL ?>/meuscompromissos" class="btn btn-secondary">Meus Compromissos</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="public-agendas-section">
        <h2>Agendas Públicas Disponíveis</h2>
        <p>Confira as agendas disponíveis para consulta pública.</p>
        
        <?php if (empty($publicAgendas)): ?>
            <div class="empty-state">
                <p>Não há agendas públicas disponíveis no momento.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table public-agendas-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Descrição</th>
                            <th>Responsável</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($publicAgendas as $agenda): ?>
                            <tr style="border-left: 4px solid <?= htmlspecialchars($agenda['color']) ?>">
                                <td><?= htmlspecialchars($agenda['title']) ?></td>
                                <td>
                                    <?php if (!empty($agenda['description'])): ?>
                                        <?= htmlspecialchars(mb_substr($agenda['description'], 0, 100)) ?>
                                        <?= (mb_strlen($agenda['description']) > 100) ? '...' : '' ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sem descrição</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($agenda['owner_name']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/public-agenda/<?= $agenda['public_hash'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Visualizar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
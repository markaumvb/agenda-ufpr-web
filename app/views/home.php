<?php
$currentUri = $_SERVER['REQUEST_URI'];
$isHomePage = ($currentUri == '/' || $currentUri == '/agenda_ufpr/' || $currentUri == '/agenda_ufpr/index.php');
?>

<div class="page-header">
    <div class="header-container">
        <h1>Sistema de Agendamento UFPR</h1>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="header-actions">
                <a href="<?= PUBLIC_URL ?>/login" class="btn btn-primary">Entrar no Sistema</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="welcome-section">
        <p class="welcome-text">
            Bem-vindo ao sistema de agendamento da UFPR Jandaia do Sul. 
            Aqui você pode visualizar agendas públicas e criar compromissos.
        </p>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span>Logado como: <strong><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']) ?></strong></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($publicAgendas)): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h3>Nenhuma agenda pública encontrada</h3>
        <p>Não há agendas públicas disponíveis no momento.</p>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= PUBLIC_URL ?>/agendas" class="btn btn-primary">
                <i class="fas fa-plus"></i> Criar Nova Agenda
            </a>
        <?php else: ?>
            <a href="<?= PUBLIC_URL ?>/login" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="public-agendas-section">
        <h2><i class="fas fa-globe"></i> Agendas Públicas</h2>
        <p>Visualize os compromissos das agendas públicas disponíveis e crie novos compromissos.</p>
        
        <div class="public-agendas-table-container">
            <table class="public-agendas-table">
                <thead>
                    <tr>
                        <th>Agenda</th>
                        <th>Descrição</th>
                        <th>Responsável</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($publicAgendas as $agenda): ?>
                        <tr style="border-left-color: <?= htmlspecialchars($agenda['color'] ?? '#3788d8') ?>;">
                            <td>
                                <strong><?= htmlspecialchars($agenda['title']) ?></strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($agenda['description'] ?: 'Sem descrição') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($agenda['owner_name'] ?? 'N/A') ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (!empty($agenda['public_hash'])): ?>
                                        <a href="<?= BASE_URL ?>/public-agenda/<?= $agenda['public_hash'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> Ver Agenda
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // MODIFICAÇÃO PRINCIPAL: Detectar se usuário está logado
                                    if (isset($_SESSION['user_id'])): 
                                        // Usuário LOGADO: usar rota para usuários logados
                                    ?>
                                        <a href="<?= BASE_URL ?>/compromissos/new-public?agenda_id=<?= $agenda['id'] ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> Criar Compromisso
                                        </a>
                                    <?php else: 
                                        // Usuário NÃO LOGADO: manter fluxo externo original
                                    ?>
                                        <a href="<?= BASE_URL ?>/compromissos/external-form?agenda_id=<?= $agenda['id'] ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> Criar Compromisso
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
// Script para página inicial (já existente)
document.addEventListener('DOMContentLoaded', function() {
    // Código específico da página inicial pode ficar aqui
    console.log('Página inicial carregada');
});
</script>
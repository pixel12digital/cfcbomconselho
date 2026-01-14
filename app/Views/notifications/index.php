<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Notificações</h1>
            <p class="text-muted">Suas notificações do sistema</p>
        </div>
        <?php if ($unreadCount > 0): ?>
        <form method="POST" action="<?= base_path('notificacoes/ler-todas') ?>" style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-outline">
                Marcar todas como lidas
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: var(--spacing-md);">
    <div class="card-body">
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="<?= base_path('notificacoes?filter=all') ?>" 
               class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>">
                Todas
            </a>
            <a href="<?= base_path('notificacoes?filter=unread') ?>" 
               class="btn <?= $filter === 'unread' ? 'btn-primary' : 'btn-outline' ?>">
                Não lidas <?= $unreadCount > 0 ? "({$unreadCount})" : '' ?>
            </a>
        </div>
    </div>
</div>

<!-- Lista de Notificações -->
<?php if (empty($notifications)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">
                <?= $filter === 'unread' ? 'Você não tem notificações não lidas.' : 'Você não tem notificações.' ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <div style="display: flex; flex-direction: column;">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $isUnread = !$notification['is_read'];
                    $createdAt = new \DateTime($notification['created_at']);
                    $formattedDate = $createdAt->format('d/m/Y H:i');
                    
                    // Se tiver link, tornar a notificação clicável
                    $hasLink = !empty($notification['link']);
                    $notificationClass = $isUnread ? 'notification-unread' : 'notification-read';
                    ?>
                    <div class="notification-item <?= $notificationClass ?>" 
                         style="padding: var(--spacing-md); border-bottom: 1px solid var(--color-border); <?= $hasLink ? 'cursor: pointer;' : '' ?>"
                         <?php if ($hasLink): ?>onclick="window.location.href='<?= base_path($notification['link']) ?>'"<?php endif; ?>>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--spacing-md);">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-xs);">
                                    <h4 style="margin: 0; font-size: var(--font-size-base); font-weight: var(--font-weight-semibold);">
                                        <?= htmlspecialchars($notification['title']) ?>
                                    </h4>
                                    <?php if ($isUnread): ?>
                                        <span style="display: inline-block; width: 8px; height: 8px; background-color: var(--color-primary); border-radius: 50%; flex-shrink: 0;"></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($notification['body'])): ?>
                                <p style="margin: 0 0 var(--spacing-xs) 0; color: var(--color-text-muted); font-size: var(--font-size-sm);">
                                    <?= htmlspecialchars($notification['body']) ?>
                                </p>
                                <?php endif; ?>
                                <div style="font-size: var(--font-size-xs); color: var(--color-text-muted);">
                                    <?= $formattedDate ?>
                                </div>
                            </div>
                            <?php if ($isUnread && !$hasLink): ?>
                            <form method="POST" action="<?= base_path("notificacoes/{$notification['id']}/ler") ?>" 
                                  style="display: inline;"
                                  onclick="event.stopPropagation();">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" class="btn btn-sm btn-outline" title="Marcar como lida">
                                    Marcar como lida
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.notification-item {
    transition: background-color var(--transition-base);
}

.notification-item:hover {
    background-color: var(--color-bg-light);
}

.notification-unread {
    background-color: rgba(2, 58, 141, 0.05);
}

.notification-read {
    opacity: 0.8;
}
</style>


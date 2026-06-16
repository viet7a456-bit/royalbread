<?php $pageTitle = 'Tin nhắn khách hàng'; ?>

<section class="admin-grid admin-grid--dashboard">
    <article class="admin-panel-card admin-panel-card--wide">
        <div class="admin-section-head">
            <div>
                <p class="admin-kicker">Liên hệ từ website</p>
                <h2>Danh sách lời nhắn từ form liên hệ</h2>
            </div>
        </div>

        <?php if ($messages !== []): ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Thời gian mong muốn</th>
                            <th>Chủ đề</th>
                            <th>Nội dung</th>
                            <th>Ngày gửi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?= e($message['customer_name']) ?></td>
                                <td><?= e($message['phone']) ?></td>
                                <td><?= e($message['contact_time']) ?></td>
                                <td><?= e($message['subject'] ?? '') ?></td>
                                <td><?= e($message['message']) ?></td>
                                <td><?= e($message['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="admin-empty-state">
                <strong>Chưa có tin nhắn nào</strong>
                <p>Khi khách gửi form liên hệ từ website, dữ liệu sẽ xuất hiện tại đây.</p>
            </div>
        <?php endif; ?>
    </article>

    <article class="admin-panel-card">
        <div class="admin-section-head">
            <div>
                <p class="admin-kicker">Chat trực tuyến</p>
                <h2>Danh sách cuộc trò chuyện</h2>
            </div>
        </div>

        <?php if ($threads !== []): ?>
            <div class="admin-message-list admin-live-chat-thread-list">
                <?php foreach ($threads as $thread): ?>
                    <a class="admin-live-chat-thread<?= !empty($activeThread) && (int) $activeThread['id'] === (int) $thread['id'] ? ' is-active' : '' ?>" href="<?= e(url('admin/messages?thread=' . (int) $thread['id'])) ?>">
                        <div>
                            <strong><?= e($thread['customer_name'] ?? $thread['customer_username'] ?? 'Khách hàng') ?></strong>
                            <span><?= e($thread['customer_phone'] ?? '') ?></span>
                        </div>
                        <p><?= e($thread['last_message'] ?? 'Chưa có nội dung') ?></p>
                        <div class="admin-live-chat-thread__meta">
                            <small><?= e($thread['status'] === 'closed' ? 'Đã đóng' : 'Đang mở') ?></small>
                            <?php if ((int) ($thread['unread_customer_messages'] ?? 0) > 0): ?>
                                <em><?= e((string) $thread['unread_customer_messages']) ?> chưa đọc</em>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="admin-empty-state">
                <strong>Chưa có chat trực tiếp nào</strong>
                <p>Khi khách nhắn từ khu tài khoản, hội thoại sẽ hiện ở đây để admin phản hồi.</p>
            </div>
        <?php endif; ?>
    </article>

    <article class="admin-panel-card">
        <div class="admin-section-head">
            <div>
                <p class="admin-kicker">Khung chat</p>
                <h2><?= !empty($activeThread) ? 'Đang trò chuyện với #' . (int) $activeThread['id'] : 'Chọn một cuộc trò chuyện' ?></h2>
            </div>

            <?php if (!empty($activeThread)): ?>
                <form method="post" action="<?= e(url('admin/messages/thread-status')) ?>" class="admin-live-chat-status-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="thread_id" value="<?= e((string) $activeThread['id']) ?>">
                    <input type="hidden" name="action" value="<?= e(($activeThread['status'] ?? 'open') === 'closed' ? 'reopen' : 'close') ?>">
                    <button type="submit" class="admin-btn admin-btn--ghost">
                        <?= ($activeThread['status'] ?? 'open') === 'closed' ? 'Mở lại chat' : 'Đóng chat' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($activeThread)): ?>
            <div class="admin-live-chat-window">
                <div class="admin-live-chat-meta">
                    <strong><?= e($activeThread['customer_name'] ?? $activeThread['customer_username'] ?? 'Khách hàng') ?></strong>
                    <span><?= e($activeThread['customer_email'] ?? '') ?></span>
                    <span><?= e($activeThread['customer_phone'] ?? '') ?></span>
                </div>

                <div class="admin-live-chat-messages">
                    <?php foreach ($chatMessages as $message): ?>
                        <?php $isAdminMessage = ($message['sender_type'] ?? '') === 'admin'; ?>
                        <article class="admin-live-chat__row <?= $isAdminMessage ? 'is-admin' : 'is-customer' ?>">
                            <div class="admin-live-chat-bubble <?= $isAdminMessage ? 'is-admin' : 'is-customer' ?>">
                                <div class="admin-live-chat-bubble__head">
                                    <strong><?= e($message['sender_name'] ?? ($isAdminMessage ? 'Admin' : 'Khách hàng')) ?></strong>
                                    <span><?= e($message['created_at']) ?></span>
                                </div>
                                <p><?= nl2br(e($message['message'])) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <form method="post" action="<?= e(url('admin/messages/send')) ?>" class="admin-live-chat-reply">
                    <?= csrf_field() ?>
                    <input type="hidden" name="thread_id" value="<?= e((string) $activeThread['id']) ?>">
                    <label>
                        Phản hồi cho khách
                        <textarea name="message" rows="4" placeholder="Nhập nội dung cần hỗ trợ khách hàng..." required></textarea>
                    </label>
                    <button type="submit" class="admin-btn">Gửi phản hồi</button>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-empty-state">
                <strong>Chưa chọn cuộc trò chuyện nào</strong>
                <p>Hãy bấm một khách ở cột bên trái để xem lịch sử chat và phản hồi trực tiếp.</p>
            </div>
        <?php endif; ?>
    </article>
</section>

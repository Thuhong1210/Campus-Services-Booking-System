<?php

declare(strict_types=1);

class NotificationController extends Controller
{
    private NotificationRepository $notificationRepo;

    public function __construct()
    {
        $this->notificationRepo = new NotificationRepository();
    }

    public function index(): void
    {
        Middleware::auth();

        $userId = (int) Auth::id();
        $filters = [
            'type' => $this->get()['type'] ?? '',
        ];

        if (($this->get()['is_read'] ?? '') !== '') {
            $filters['is_read'] = (int) $this->get()['is_read'];
        }

        $page = max(1, (int) ($this->get()['p'] ?? 1));
        $perPage = 20;
        $allNotifications = $this->notificationRepo->findByUser($userId, $filters, 1000, 0);
        $total = count($allNotifications);
        $pagination = paginate($total, $page, $perPage, 'index.php?page=notifications');
        $notifications = array_slice($allNotifications, $pagination['offset'], $perPage);
        $unreadCount = $this->notificationRepo->countUnread($userId);

        $this->view('notifications/index', [
            'title' => 'Notifications',
            'notifications' => $notifications,
            'filters' => $filters,
            'pagination' => $pagination,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead(): void
    {
        Middleware::auth();
        $this->verifyCsrf();

        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            Flash::error('Invalid notification ID.');
            redirect('index.php?page=notifications');
        }

        if ($this->notificationRepo->markRead($id, (int) Auth::id())) {
            Flash::success('Notification marked as read.');
        } else {
            Flash::error('Notification not found.');
        }

        redirect('index.php?page=notifications');
    }

    public function markAllRead(): void
    {
        Middleware::auth();
        $this->verifyCsrf();

        $this->notificationRepo->markAllRead((int) Auth::id());
        Flash::success('All notifications marked as read.');
        redirect('index.php?page=notifications');
    }
}

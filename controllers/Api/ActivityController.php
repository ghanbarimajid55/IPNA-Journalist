<?php

class Api_ActivityController extends BaseController
{
    private Activity $activityModel;

    public function __construct()
    {
        parent::__construct();
        $this->activityModel = new Activity();
    }

    public function index()
    {
        $auth = new Auth();
        if (!$auth->check()) {
            $this->json(['success' => false, 'message' => 'غير مجاز'], 401);
            return;
        }

        $currentUser = $auth->user();
        $filters = [];

        // Non-admins are always scoped to their own activities
        if ($currentUser['role'] !== 'admin') {
            $filters['user_id'] = $currentUser['id'];
        } elseif (!empty($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }

        if (!empty($_GET['activity_type_id'])) {
            $filters['activity_type_id'] = (int)$_GET['activity_type_id'];
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        if (!empty($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }

        $limit = !empty($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;

        $activities = $this->activityModel->findAll($filters, 'created_at DESC', $limit);
        $total = $this->activityModel->count($filters);

        $this->json([
            'success' => true,
            'activities' => $activities,
            'total' => $total,
            'filters' => $filters,
        ]);
    }
}

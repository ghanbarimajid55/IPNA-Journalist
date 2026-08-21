<?php

class Photographer_ActivityController extends Role_ActivityControllerBase
{
    protected function roleName(): string { return 'photographer'; }
    protected function viewPrefix(): string { return 'photographer.activities'; }
    protected function basePath(): string { return '/photographer/activities'; }

    public function create()
    {
        $auth = new Auth();
        if (!$auth->requireRole($this->roleName(), 'admin')) {
            return;
        }

        $this->view($this->viewPrefix() . '.create', [
            'photoTypes' => $this->activityTypeModel->getByCategory('photo'),
            'videoTypes' => $this->activityTypeModel->getByCategory('video'),
            'activityTypes' => $this->activityTypeModel->findAll(),
            'today' => date('Y-m-d'),
        ]);
    }
}

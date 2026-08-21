<?php

class Photographer_TargetController extends Role_TargetControllerBase
{
    protected function roleName(): string { return 'photographer'; }
    protected function viewPrefix(): string { return 'photographer.targets'; }
    protected function basePath(): string { return '/photographer/targets'; }
}

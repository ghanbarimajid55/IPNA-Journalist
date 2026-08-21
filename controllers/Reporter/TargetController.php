<?php

class Reporter_TargetController extends Role_TargetControllerBase
{
    protected function roleName(): string { return 'reporter'; }
    protected function viewPrefix(): string { return 'reporter.targets'; }
    protected function basePath(): string { return '/reporter/targets'; }
}

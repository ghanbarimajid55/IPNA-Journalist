<?php

class Reporter_ActivityController extends Role_ActivityControllerBase
{
    protected function roleName(): string { return 'reporter'; }
    protected function viewPrefix(): string { return 'reporter.activities'; }
    protected function basePath(): string { return '/reporter/activities'; }
}

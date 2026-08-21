<?php

class Reporter_ReportController extends Role_ReportControllerBase
{
    protected function roleName(): string { return 'reporter'; }
    protected function viewPrefix(): string { return 'reporter.reports'; }
    protected function basePath(): string { return '/reporter/reports'; }
}

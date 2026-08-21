<?php

class Photographer_ReportController extends Role_ReportControllerBase
{
    protected function roleName(): string { return 'photographer'; }
    protected function viewPrefix(): string { return 'photographer.reports'; }
    protected function basePath(): string { return '/photographer/reports'; }
}

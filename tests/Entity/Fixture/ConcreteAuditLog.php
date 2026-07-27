<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Fixture;

use Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog;

/**
 * Stands in for a project's own App\Entity\AuditLog. Deliberately adds nothing:
 * the point is to prove BaseAuditLog is usable with an empty subclass.
 */
class ConcreteAuditLog extends BaseAuditLog
{
}

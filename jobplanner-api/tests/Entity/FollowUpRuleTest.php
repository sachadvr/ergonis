<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\FollowUpRule;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class FollowUpRuleTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $rule = new FollowUpRule();

        $this->assertSame(7, $rule->getDaysWithoutReply());
        $this->assertSame(FollowUpRule::TEMPLATE_FOLLOW_UP, $rule->getTemplateType());
        $this->assertTrue($rule->isEnabled());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rule->getCreatedAt());
    }

    public function testSettersAndOwnerWork(): void
    {
        $owner = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $rule = (new FollowUpRule())
            ->setOwner($owner)
            ->setDaysWithoutReply(14)
            ->setTemplateType(FollowUpRule::TEMPLATE_THANK_YOU)
            ->setEnabled(false);

        $this->assertSame($owner, $rule->getOwner());
        $this->assertSame(14, $rule->getDaysWithoutReply());
        $this->assertSame(FollowUpRule::TEMPLATE_THANK_YOU, $rule->getTemplateType());
        $this->assertFalse($rule->isEnabled());
    }
}

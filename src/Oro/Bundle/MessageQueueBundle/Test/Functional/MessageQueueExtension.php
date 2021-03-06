<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

/**
 * It is expected that this trait will be used in classes that have "getContainer" static method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait MessageQueueExtension
{
    use MessageQueueAssertTrait;

    /**
     * Removes all sent messages.
     *
     * @before
     */
    public function setUpMessageCollector()
    {
        self::getMessageCollector()
            ->clear();
    }

    /**
     * Removes all sent messages.
     *
     * @after
     */
    public function tearDownMessageCollector()
    {
        self::getMessageCollector()
            ->clear();
    }
}

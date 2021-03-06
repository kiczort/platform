<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Model\Error;

abstract class BuildResultDocument implements ProcessorInterface
{
    /** @var DocumentBuilderInterface */
    protected $documentBuilder;

    /** @var ErrorCompleterInterface */
    protected $errorCompleter;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param DocumentBuilderInterface $documentBuilder
     * @param ErrorCompleterInterface  $errorCompleter
     * @param LoggerInterface          $logger
     */
    public function __construct(
        DocumentBuilderInterface $documentBuilder,
        ErrorCompleterInterface $errorCompleter,
        LoggerInterface $logger
    ) {
        $this->documentBuilder = $documentBuilder;
        $this->errorCompleter = $errorCompleter;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasErrors()) {
            try {
                $this->documentBuilder->setErrorCollection($context->getErrors());
                // remove errors from the Context to avoid processing them by other processors
                $context->resetErrors();
            } catch (\Exception $e) {
                $this->processException($context, $e);
                $context->resetErrors();
            }
            $context->setResponseDocumentBuilder($this->documentBuilder);
        } elseif ($context->hasResult()) {
            $responseStatusCode = $context->getResponseStatusCode();
            if (null === $responseStatusCode || $responseStatusCode < Response::HTTP_BAD_REQUEST) {
                try {
                    $this->processResult($context);
                } catch (\Exception $e) {
                    $this->processException($context, $e);
                }
                $context->setResponseDocumentBuilder($this->documentBuilder);
            }
        }
        $context->removeResult();
    }

    /**
     * @param Context    $context
     * @param \Exception $e
     */
    protected function processException(Context $context, \Exception $e)
    {
        $context->setResponseStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $error = Error::createByException($e);
        $this->errorCompleter->complete($error);
        $this->documentBuilder->clear();
        $this->documentBuilder->setErrorObject($error);

        $this->logger->error(
            sprintf('Building of the result document failed.'),
            [
                'exception' => $e,
                'action'    => $context->getAction(),
                'entity'    => $context->getClassName()
            ]
        );
    }

    /**
     * @param Context $context
     */
    abstract protected function processResult(Context $context);
}

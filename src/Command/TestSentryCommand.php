<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class TestSentryCommand extends Command
{
    public function __construct(private readonly LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->debug('Just a de🪲 message');
        $this->logger->info('Some ℹ️nformation');
        $this->logger->notice('I noticed you 👀');
        $this->logger->warning('Something is going on 🫦');
        $this->logger->error('Something is going terribly wrong 🙀');
        $this->logger->critical('Uhm, hold on 😳');
        $this->logger->alert('Oh boy 😱');
        $this->logger->emergency('This is fine 🔥');

        try {
            throw new \UnexpectedValueException('I did not expect that 🤯', 1673054701);
        } catch (\Throwable $e) {
            $this->logger->error('But I caught it 😅', ['exception' => $e]);
        }

        throw new \LogicException('I don\'t think this makes much sense 🤔', 1673056782);
    }
}

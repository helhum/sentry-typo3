<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class TestSentryCommand extends Command
{
    public function __construct(private readonly LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'log',
            null,
            InputOption::VALUE_NONE
        );
        $this->addOption(
            'throw',
            null,
            InputOption::VALUE_NONE
        );
        $this->addOption(
            'warning',
            null,
            InputOption::VALUE_NONE
        );
        $this->addOption(
            'deprecation',
            null,
            InputOption::VALUE_NONE
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->error(
            'If you see this message in Sentry, everything is configured correctly 🎉',
            [
                'example-extra' => 'This is some extra, which shows up',
                'tags' => [
                    'example.tag' => 'Tags can be set with loggers as well',
                ],
                'fingerprint' => [
                    'Sentry issues can be disambiguated with setting a custom fingerprint',
                    'Usually this should not be necessary',
                ]
            ]
        );
        if ($input->getOption('deprecation') === true) {
            trigger_error('Oh, no, not this again, it\'s so old 👴', \E_USER_DEPRECATED);
        }
        if ($input->getOption('log') === true) {
            $this->logger->debug('Just a de🪲 message');
            $this->logger->info('Some ℹ️nformation');
            $this->logger->notice('I noticed you 👀');
            $this->logger->warning('Something is going on 🫦');
            $this->logger->error('Something is going terribly wrong 🙀');
            $this->logger->critical('Uhm, hold on 😳');
            $this->logger->alert('Oh boy 😱');
            $this->logger->emergency('This is fine 🔥');
        }

        if ($input->getOption('warning') === true) {
            $foo = $GLOBALS['this-triggers-warning'];
        }

        if ($input->getOption('throw') === true) {
            try {
                throw new \UnexpectedValueException('I did not expect that 🤯', 1673054701);
            } catch (\Throwable $e) {
                $this->logger->error('But I caught it 😅', ['exception' => $e]);
            }
            throw new \LogicException('I don\'t think this makes much sense 🤔', 1673056782);
        }

        return $foo ?? self::SUCCESS;
    }
}

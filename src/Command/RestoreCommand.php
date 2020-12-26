<?php
declare(strict_types = 1);
/**
 * @author Mehrez Labidi
 */

namespace App\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;
use Doctrine\DBAL\Driver\Connection;
use Psr\Log\LoggerInterface;



class RestoreCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:create-user';
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct( Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select your favorite colors (defaults to red and blue)',
            ['red', 'blue', 'yellow'],
            '0,1'
        );
        $question->setMultiselect(true);

        $colors = $helper->ask($input, $output, $question);
        $output->writeln('You have just selected: ' . implode(', ', $colors));


        $databases = $this->connection->fetchFirstColumn('show databases;');
        $output->writeln( implode(',',$databases));



        return Command::SUCCESS;

    }
}
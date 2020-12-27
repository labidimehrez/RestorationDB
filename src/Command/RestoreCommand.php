<?php
declare(strict_types=1);
/**
 * @author Mehrez Labidi
 */

namespace App\Command;

use Doctrine\DBAL\Driver\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class RestoreCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'RestoreDB';
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ParameterBagInterface
     */
    private $params;

    public function __construct(ParameterBagInterface $params, Connection $connection, LoggerInterface $logger)
    {
        $this->params = $params;
        $this->connection = $connection;
        $this->logger = $logger;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $remote['password'] = $this->params->get('remote_password');
        $remote['login'] = $this->params->get('remote_user');
        $remote['hote'] = $this->params->get('remote_database_host');
        $remotedb['login'] = $this->params->get('remote_database_user');
        $remotedb['password'] = $this->params->get('remote_database_password');
        $localdb['login'] = $this->params->get('database_user');
        $localdb['password'] = $this->params->get('database_password');
        $databases = $this->params->get('databases');

        $helper = $this->getHelper('question');
        $question1 = new ChoiceQuestion(
            'You really want to restore the database ?',
            ['Yes', 'No'],
            '0'
        );
        $question1->setMultiselect(false);
        $reponse1 = $helper->ask($input, $output, $question1);
        if ($reponse1 == 'Yes') {
            $output->writeln('OK');
        } else {
            $output->writeln('Bye..');
            return Command::SUCCESS;
        }
        $question2 = new ChoiceQuestion(
            'Which base do you want to restore ?',
            $databases
        );
        $question2->setMultiselect(false);
        $reponse2 = $helper->ask($input, $output, $question2);
        $databaseToRestore = $reponse2;
        $question3 = new ChoiceQuestion(
            "You want to restore  $reponse2 ?",
            ['Yes', 'No'],
            '0'
        );
        $reponse3 = $helper->ask($input, $output, $question3);
        if ($reponse3 == 'No') {
            $output->writeln('Bye..');
            return Command::SUCCESS;
        }
        $question4 = new ChoiceQuestion(
            "You want to restore the whole database $reponse2 ?",
            ['Yes', 'Only One table'],
            '0'
        );
        $reponse4 = $helper->ask($input, $output, $question4);
        if ($reponse4 == "Only One table") {
            try {
                // tables to restore must already exist
                $tables = $this->connection->fetchFirstColumn("SHOW TABLES FROM $databaseToRestore;");
            } catch (\Exception $e) {
                $output->writeln("$databaseToRestore is empty or doesnt exit ..");
                $output->writeln('Bye..');
                return Command::SUCCESS;
            }
            $question5 = new ChoiceQuestion(
                "Which table do you want to restore  for $databaseToRestore database ?",
                $tables
            );
            $reponse5 = $helper->ask($input, $output, $question5);
        }
        $tableRestore = (isset($reponse5)) ? $reponse5 : "";
        $cmd = sprintf("
        sshpass -p %s ssh %s@%s   'mysqldump -u%s -p%s %s %s'  | \
        mysql -u %s -p%s   %s  
        ",
            $remote['password'],
            $remote['login'],
            $remote['hote'],
            $remotedb['login'],
            $remotedb['password'], $databaseToRestore, $tableRestore,
            $localdb['login'],
            $localdb['password'],
            $databaseToRestore
        );
        try {
            $this->connection->executeQuery("CREATE DATABASE $databaseToRestore;");
        } catch (\Exception $e) {
             $this->logger->info($e->getMessage());
        }
        $output->writeln(' ');
        $progressBar = new ProgressBar($output, 100);
        $progressBar->setFormat('debug');
//      $progressBar->start();
//      $i = 0;
//      while ($i++ < 100) {
//        $progressBar->advance(2);
//      }
        $output->writeln(' ');
        $outputCmd = shell_exec($cmd);
        $this->logger->info($cmd);
        $progressBar->finish();
        $output->writeln(' ');
        return Command::SUCCESS;
    }
}
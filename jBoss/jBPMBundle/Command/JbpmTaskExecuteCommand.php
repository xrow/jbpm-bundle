<?php

namespace jBoss\jBPMBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use jBoss\jBPMBundle\Service\TaskService As Task;

class JbpmTaskExecuteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('jbpm:task-execute')
            ->setDescription('JBPM task executer')
            ->addArgument('host', InputArgument::OPTIONAL, 'API BaseUrl')
            ->addArgument('user', InputArgument::OPTIONAL, 'API username')
            ->addArgument('password', InputArgument::OPTIONAL, 'API password')
        ;
    }
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $currentTime = $this->getLoggerDateTime();
        $output->writeln([
            '[' . $currentTime . ']',
            'JBPM Task Execute Initialize',
            '============================',
            '',
        ]);
        $host = $input->getArgument('host');
        $user = $input->getArgument('user');
        $password = $input->getArgument('password');
        if (empty($host)) {
            $question = array(
                    "<comment>Enter API BaseUrl</comment>\n",
                    "<question>host:</question> ",
            );
            $host = $this->getHelper('dialog')->askAndValidate($output, $question, function($input) {
                if (empty($input)) {
                    throw new \InvalidArgumentException('Invalid host');
                }
                return $input;
            }, 10, null);

            $input->setArgument('host', $host);
        }
        if (empty($user)) {
            $question = array(
                    "<comment>Enter API username</comment>\n",
                    "<question>Username:</question> ",
            );
            $user = $this->getHelper('dialog')->askAndValidate($output, $question, function($input) {
                if (empty($input)) {
                    throw new \InvalidArgumentException('Invalid username');
                }
                return $input;
            }, 10, null);

            $input->setArgument('user', $user);
        }

        if (empty($password)) {
            $question = array(
                    "<comment>Enter API password</comment>\n",
                    "<question>Password:</question> ",
            );
            $password = $this->getHelper('dialog')->askHiddenResponseAndValidate($output, $question, function($input) {
                if (empty($input)) {
                    throw new \InvalidArgumentException('Invalid password');
                }
                return $input;
            }, 10, null);

            $input->setArgument('password', $password);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errorMsg = "";
        $errorCode = NULL;
        $errorMessages = [];
        $responseData = [];

        $host = $input->getArgument('host');
        $user = $input->getArgument('user');
        $password = $input->getArgument('password');
        if (!empty($host) && !empty($user) && !empty($password)) {
            try {
                $currentTime = $this->getLoggerDateTime();
                $output->writeln(sprintf("[%s] Task executor started for the user %s", $currentTime, $user));
                $argument = array(
                    'host' => $host,
                    'user' => $user,
                    'password' => $password
                );

                $taskObj = new Task($output);
                if ($taskObj->setData($argument)) {
                    // call the Execute Task service.
                    $responseData = $taskObj->executeTask();
                }
                $errorCode = $taskObj->getErrorCode();
                $errorMessages = $taskObj->getErrorMsgs();
                if ( 0 == count($errorMessages)) {
                    $currentTime = $this->getLoggerDateTime();
                    $output->writeln(sprintf("[%s] Task execution completed for the user :%s", $currentTime, $user));
                    exit;
                } else {
                    if (0 < count($errorMessages)) {
                        $errorMsg = implode(', ', $errorMessages);
                        $currentTime = $this->getLoggerDateTime();
                        $output->writeln(sprintf("[%s] Task executor stoped due error, error code %d and error message is %s", $currentTime, $errorCode, $errorMsg));
                    } else {
                        $currentTime = $this->getLoggerDateTime();
                        $output->writeln(sprintf("[%s] Task executor stoped.", $currentTime));
                    }
                    exit;
                }
            } catch (Exception $e) {
                $currentTime = $this->getLoggerDateTime();
                $output->writeln(sprintf("[%s] Task executor stoped due to %s", $currentTime, $e->getMessage()));
                exit;
            }
        } else {
            $currentTime = $this->getLoggerDateTime();
            $output->writeln(sprintf("[%s] Task executor stoped due to required params cannot be empty", $currentTime));
            exit;
        }
    }

    /**
    * this mehod used to get current date and time as string
    * @param no param
    * @return string 
    */
    private function getLoggerDateTime()
    {
        $dateTime = new \DateTime();
        return $dateTime->format('Y-m-d H:i:s');
    }
}
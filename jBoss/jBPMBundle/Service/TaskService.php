<?php

namespace jBoss\jBPMBundle\Service;

use Symfony\Component\Console\Output\OutputInterface;
use jBoss\jBPMBundle\Service\JBPMConnectService;

class TaskService
{
	/**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var array
     */
    protected $taskIds;

    /**
     * @var array
     */
    protected $processInstanceIds;

    /**
     * @var array
     */
    protected $status;

    /**
     * @var array
     */
    protected $filterStatus;

    /**
     * @var array
     */
    protected $taskOwners;

    /**
     * @var array
     */
    protected $potentialOwners; 

    /**
     * @var number
     */
    protected $errorCode;

    /**
     * @var array
     */
    protected $errorMessages;

    /**
     * @var array
     */
    protected $validFilterStatus;
    protected $validUpdateStatus;
    protected $allowedStatusToStop;
    protected $allowedStatusToSuspend;
    protected $allowedStatusToRelease;
    protected $allowedStatusToComplete;
    protected $allowedStatusToStart;
    protected $allowedStatusToClaim;
    protected $allowedStatusToResume;
    protected $allowedTaskExecute;

    protected $output;

    /* below constants to update the task status in api call*/
    const STATUS_STOP = 'stop';
    const STATUS_START = 'start';
    const STATUS_CLAIM = 'claim';
    const STATUS_RESUME = 'resume';
    const STATUS_SUSPEND = 'suspend';
    const STATUS_RELEASE = 'release';
    const STATUS_COMPLETE = 'complete';
    
    /* below constants to filter the task status and update request of status*/
    const ACTION_STOP = 'Stop';
    const ACTION_READY = 'Ready';
    const ACTION_START = 'Start';
    const ACTION_CLAIM = 'Claim';
    const ACTION_FAILED = 'Failed';
    const ACTION_RESUME = 'Resume';
    const ACTION_SUSPEND = 'Suspend';
    const ACTION_RELEASE = 'Release';
    const ACTION_COMPLETE = 'Complete';
    const ACTION_RESERVED = 'Reserved';
    const ACTION_SUSPENDED = 'Suspended';
    const ACTION_COMPLETED = 'Completed';
    const ACTION_INPROGRESS = 'InProgress';

	/**
     * class constructor
     */
    public function __construct($output = NULL)
    {
    	$this->errorCode = 0;
        $this->validFilterStatus = array(
            TaskService::ACTION_READY,
            TaskService::ACTION_RESERVED,
            TaskService::ACTION_INPROGRESS,
            TaskService::ACTION_COMPLETED,
            TaskService::ACTION_SUSPENDED,
            TaskService::ACTION_FAILED,
            //TaskService::ACTION_STOP,
        );
        $this->validUpdateStatus = array(
            TaskService::ACTION_START,
            TaskService::ACTION_STOP,
            TaskService::ACTION_CLAIM,
            TaskService::ACTION_RESUME,
            TaskService::ACTION_SUSPEND,
            TaskService::ACTION_RELEASE,
            TaskService::ACTION_COMPLETE,
        );
        $this->allowedStatusToStop = array(
            TaskService::ACTION_INPROGRESS,
        );
        $this->allowedStatusToSuspend = array(
            TaskService::ACTION_INPROGRESS,
            TaskService::ACTION_READY,
            TaskService::ACTION_RESERVED,
        );
        $this->allowedStatusToRelease = array(
            TaskService::ACTION_INPROGRESS,
            TaskService::ACTION_RESERVED,
        );
        $this->allowedStatusToComplete = array(
            TaskService::ACTION_INPROGRESS,
        );
        $this->allowedStatusToStart = array(
            TaskService::ACTION_READY,
            TaskService::ACTION_RESERVED,
        );
        $this->allowedStatusToClaim = array(
            TaskService::ACTION_READY,
        );
        $this->allowedStatusToResume = array(
            TaskService::ACTION_RESERVED,
            TaskService::ACTION_COMPLETED,
        );
        $this->allowedTaskExecute = array(
            TaskService::ACTION_RESERVED,
            TaskService::ACTION_INPROGRESS,
        );
        $this->output = $output;
    }

    /**
     * This method used to set all the params of task and do the basic required validation
     * @param array $params
     */
    public function setData($params)
    {
    	foreach ($params as $key => $value) {
    		switch ($key) {
    			case 'host':
    				$this->baseUrl = $this->checkNotEmptyNotNull($value);
    				break;
    			case 'user':
    				$this->username = $this->checkNotEmptyNotNull($value);
    				break;
    			case 'password':
    				$this->password = $this->checkNotEmptyNotNull($value);
    				break;
    			case 'filterStatus':
                    $this->filterStatus = $this->checkFilterStatus($value);
                    break;
                case 'status':
    				$this->status = $this->checkStatus($value);
    				break;
    			case 'potential_owner':
    				$this->potentialOwners = $this->checkOwners($value, "potentialOwner");
    				break;
    			case 'task_owner':
    				$this->taskOwners = $this->checkOwners($value, "taskOwner");
    				break;
    			case 'task_id':
    				$this->taskIds = $this->checkIds($value, "taskId");
    				break;
    			case 'process_instance_id':
    				$this->processInstanceIds = $this->checkIds($value, "processInstanceId");
    				break;
    			default:
    				break;
    		}
    	}
    	if ($this->validateRequired()) {
    		return true;
    	} else {
    		return false;
    	}
    }

    /**
     * This method used validate required params to connect JBPM are host, username, passowrd
     * @param array $params
     */
    protected function validateRequired()
    {
    	if (is_null($this->baseUrl) || is_null($this->username) || is_null($this->password)) {
    		$this->errorCode = 2;
			$this->errorMessages[$this->errorCode] = "Mandatory fields can not be empty";
			return false;
    	} else {
    		return true;
    	}
    }

    /**
     * @return number
     */
    public function getErrorCode()
    {
    	return $this->errorCode;
    }

    /**
     * @return array
     */
    public function getErrorMsgs()
    {
        return $this->errorMessages;
    }

    /**
     * this method is used to check where not errors one given parameters
     * @return boolean
     */
    protected function checkNoError()
    {
        if (0 === $this->getErrorCode() &&  0 === count($this->getErrorMsgs())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getAllTask()
    {
    	if ($this->checkNoError()) {
            $allTask = '/rest/task/query' . $this->getAllTaskQuery();
            $method = 'GET';
            $params = [];
            $connecterConfig = [
                'baseUrl' => $this->baseUrl,
                'username' => $this->username,
                'password' => $this->password
            ];
            $jbpm = new JBPMConnectService($connecterConfig);
            return $jbpm->callJbpm($allTask, $method, $params);
        } else {
            return [];
        }
    }

    /**
     * @return string
     */
    protected function getAllTaskQuery()
    {
        $queryParam = "";
        $queryArray = [];
        if (0 < count($this->filterStatus)) {
            foreach ($this->filterStatus as $value) {
                $queryArray[] = "status=$value";
            }
        }
        if (0 < count($this->potentialOwners)) {
            foreach ($this->potentialOwners as $value) {
                $queryArray[] = "potentialOwner=$value";
            }
        }
        if (0 < count($this->taskOwners)) {
            foreach ($this->taskOwners as $value) {
                $queryArray[] = "taskOwner=$value";
            }
        }
        if (0 < count($this->taskIds)) {
            foreach ($this->taskIds as $value) {
                $queryArray[] = "taskId=$value";
            }
        }
        if (0 < count($this->processInstanceIds)) {
            foreach ($this->processInstanceIds as $value) {
                $queryArray[] = "processInstanceId=$value";
            }
        }
        if (0 < count($queryArray))
            $queryParam = "?" . implode('&', $queryArray);

        return $queryParam;
    }

    /**
     * @return string
     */
    protected function checkBaseUrl($param)
    {
        if (is_null($param) || empty(trim($param))) {
            $this->errorCode = 2;
            $this->errorMessages[$this->errorCode] = "Mandatory fields can not be empty";
        } else {
            return trim($param);
        }
    }

    /**
     * @return string
     */
    protected function checkNotEmptyNotNull($param)
    {
        if (is_null($param) || empty(trim($param))) {
            return NULL;
        } else {
            return trim($param);
        }
    }

    /**
     * @param string
     * @return array
     */
    protected function checkStatus($param)
    {
        $returnArray = [];
        if (!is_null($param) && !empty($param)) {
            $statusArray = array_map('trim', explode(',', $param));
            if (1 <= count($statusArray) ) {
                $invalidCount = 0;
                foreach ($statusArray as $key => $value) {
                    if (!in_array($value, $this->validUpdateStatus)) {
                        $invalidCount++;
                    } else {
                        $returnArray[] = $value;
                    }
                }
                if (count($statusArray) === $invalidCount) {
                    $this->errorCode = 3;
                    $this->errorMessages[$this->errorCode] = "Invalid status provided";
                }
            }
        }

        return $returnArray;
    }

    /**
     * @param string
     * @return array
     */
    protected function checkFilterStatus($param)
    {
        $returnArray = [];
        if (!is_null($param) && !empty($param)) {
            $statusArray = array_map('trim', explode(',', $param));
            if (1 <= count($statusArray) ) {
                $invalidCount = 0;
                foreach ($statusArray as $key => $value) {
                    if (!in_array($value, $this->validFilterStatus)) {
                        $invalidCount++;
                    } else {
                        $returnArray[] = $value;
                    }
                }
                if (count($statusArray) === $invalidCount) {
                    $this->errorCode = 3;
                    $this->errorMessages[$this->errorCode] = "Invalid status provided";
                }
            }
        }

        return $returnArray;
    }

    /**
     * @param string $param, string $ownerName
     * @return array
     */
    protected function checkOwners($param, $ownerName)
    {
        $returnArray = [];
        if (!is_null($param) && !empty($param)) {
            $ownersArray = array_map('trim', explode(',', $param));
            if (0 < count($ownersArray)) {
                foreach ($ownersArray as $value) {
                    $invalidCount = 0;
                    if (!is_string($value) || is_numeric($value)) {
                        $invalidCount++;
                    } else {
                        $returnArray[] = $value;
                    }
                    if ($invalidCount == count($ownersArray)) {
                        if ("potentialOwner" == $ownerName) {
                            $this->errorCode = 6;
                            $this->errorMessages[$this->errorCode] = "Invalid potential owner provided";
                        }
                        if ("taskOwner" == $ownerName) {
                            $this->errorCode = 7;
                            $this->errorMessages[$this->errorCode] = "Invalid task owner provided";
                        }
                    }

                }
            }
        }

        return $returnArray;
    }

    /**
     * @param string $param string $idName
     * @return array
     */
    protected function checkIds($param, $idName)
    {
        $returnArray = [];
        if (!is_null($param) && !empty($param)) {
            $idsArray = array_map('trim', explode(',', $param));
            if (0 < count($idsArray)) {
                $invalidCount = 0;
                foreach ($idsArray as $key => $value) {
                    if (!is_null($value) && !is_numeric($value)) {
                        $invalidCount++;
                    } else if (!is_null($value) && is_numeric($value) && 0 >= $value  ) {
                        $invalidCount++;
                    } else {
                        $returnArray[] = $value;
                    }
                }
                if (count($idsArray) === $invalidCount) {
                    if ("taskId" == $idName) {
                        $this->errorCode = 4;
                        $this->errorMessages[$this->errorCode] = "Invalid task id provided";
                    }
                    if ("processInstanceId" == $idName) {
                        $this->errorCode = 5;
                        $this->errorMessages[$this->errorCode] = "Invalid process instance id provided";
                    }
                }
            }
        }

        return $returnArray;
    }

    /**
     * To validate required fields and update the status of task
     * @return array
     */
    public function updateTaskStatus()
    {
        $this->validateRequiredToUpdateStatus();
        if ($this->checkNoError()) {
            switch ($this->status[0]) {
                case TaskService::ACTION_STOP:
                    if ($this->allowToUpdateStatus(TaskService::STATUS_STOP)) {
                       return $this->processToUpdateStatus(TaskService::STATUS_STOP);
                    } else {
                        $this->errorCode = 9;
                        $this->errorMessages[$this->errorCode] = "Current status not allow to stop the task id " . $this->taskIds[0];
                        return false;
                    }
                    break;
                case TaskService::ACTION_SUSPEND:
                    if ($this->allowToUpdateStatus(TaskService::STATUS_SUSPEND)) {
                       return $this->processToUpdateStatus(TaskService::STATUS_SUSPEND);
                    } else {
                        $this->errorCode = 10;
                        $this->errorMessages[$this->errorCode] = "Current status not allow to suspend the task id " . $this->taskIds[0];
                        return false;
                    }
                    break;
                case TaskService::ACTION_RELEASE:
                    if ($this->allowToUpdateStatus(TaskService::STATUS_RELEASE)) {
                       return $this->processToUpdateStatus(TaskService::STATUS_RELEASE);
                    } else {
                        $this->errorCode = 11;
                        $this->errorMessages[$this->errorCode] = "Current status not allow to release the task id " . $this->taskIds[0];
                        return false;
                    }
                    break;
                case TaskService::ACTION_COMPLETE:
                    if ($this->allowToUpdateStatus(TaskService::STATUS_COMPLETE)) {
                       return $this->processToUpdateStatus(TaskService::STATUS_COMPLETE);
                    } else {
                        $this->errorCode = 12;
                        $this->errorMessages[$this->errorCode] = "Current status not allow to complete the task id " . $this->taskIds[0];
                        return false;
                    }
                    break;
                case TaskService::ACTION_START:
                    if ($this->allowToUpdateStatus(TaskService::STATUS_START)) {
                       return $this->processToUpdateStatus(TaskService::STATUS_START);
                    } else {
                        $this->errorCode = 13;
                        $this->errorMessages[$this->errorCode] = "Current status not allow to start the task id " . $this->taskIds[0];
                        return false;
                    }
                    break;
                case TaskService::ACTION_CLAIM:
                    if ($this->allowToUpdateStatus(TaskService::STATUS_CLAIM)) {
                       return $this->processToUpdateStatus(TaskService::STATUS_CLAIM);
                    } else {
                        $this->errorCode = 14;
                        $this->errorMessages[$this->errorCode] = "Current status not allow to claim the task id " . $this->taskIds[0];
                        return false;
                    }
                    break;
                case TaskService::ACTION_RESUME:
                    if ($this->allowToUpdateStatus(TaskService::STATUS_RESUME)) {
                       return $this->processToUpdateStatus(TaskService::STATUS_RESUME);
                    } else {
                        $this->errorCode = 15;
                        $this->errorMessages[$this->errorCode] = "Current status not allow to resume the task id " . $this->taskIds[0];
                        return false;
                    }
                    break;
                default:
                    break;
            }
        } else {
            return [];
        }
    }

    /**
     * This method used to validate the reuired params for update process
     * @return array
     */
    protected function validateRequiredToUpdateStatus()
    {
        if (1 != count($this->taskIds)) {
            $this->errorCode = 4;
            $this->errorMessages[$this->errorCode] = "Invalid task id provided";
        }
        if ((1 != count($this->status)) || (1 === count($this->status) && !in_array($this->status[0], $this->validUpdateStatus))) {
            $this->errorCode = 8;
            $this->errorMessages[$this->errorCode] = "Invalid status provided";
        }
    }

    /**
     * This method used to get current status of task
     * @return string
     */
    protected function getCurrentStatus() 
    {
        if ($this->checkNoError()) {
            $currTask = $this->getAllTask();
            if (isset($currTask['body']) && 0 < count($currTask['body']) && isset($currTask['body']['task-summary']) && isset($currTask['body']['task-summary']['status'])) {
                return $currTask['body']['task-summary']['status'];
            } else {
                return NULL;
            }
        }
    }

    /**
     * This method used to validate current status before update to new status
     * @return array
     */
    protected function allowToUpdateStatus($newStatus)
    {
        if ($this->checkNoError()) {
            $currentStatus = $this->getCurrentStatus();
            $checkAllowedStatus = [];
            switch ($newStatus) {
                case TaskService::STATUS_STOP:
                    $checkAllowedStatus = $this->allowedStatusToStop;
                    break;
                case TaskService::STATUS_SUSPEND:
                    $checkAllowedStatus = $this->allowedStatusToSuspend;
                    break;
                case TaskService::STATUS_RELEASE:
                    $checkAllowedStatus = $this->allowedStatusToRelease;
                    break;
                case TaskService::STATUS_COMPLETE:
                    $checkAllowedStatus = $this->allowedStatusToComplete;
                    break;
                case TaskService::STATUS_START:
                    $checkAllowedStatus = $this->allowedStatusToStart;
                    break;
                case TaskService::STATUS_CLAIM:
                    $checkAllowedStatus = $this->allowedStatusToClaim;
                    break;
                case TaskService::STATUS_RESUME:
                    $checkAllowedStatus = $this->allowedStatusToResume;
                    break;
                default:
                    break;
            }
            if (0 < count($checkAllowedStatus) && in_array($currentStatus, $checkAllowedStatus)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * This method used to update the task status
     * @return string
     */
    protected function processToUpdateStatus($newStatus) 
    {
        if ($this->checkNoError()) {
            $allTask = "/rest/task/" . $this->taskIds[0] . "/" . $newStatus;
            $method = 'POST';
            $params = [];
            $connecterConfig = [
                'baseUrl' => $this->baseUrl,
                'username' => $this->username,
                'password' => $this->password
            ];
            $jbpm = new JBPMConnectService($connecterConfig);
            return $jbpm->callJbpm($allTask, $method, $params);
        } else {
            return [];
        }
    }

    /**
     * This method used to execute the task
     * @return string
     */
    public function executeTask() 
    {
        if ($this->checkNoError()) {
            $responceArray = [];
            $currentTime = $this->getLoggerDateTime();
            $this->output->writeln("[" . $currentTime . "] " . TaskService::ACTION_RESERVED . " task execute initialize.");
            $responceArray[] = $this->processExecuteTask(TaskService::ACTION_RESERVED);
            $currentTime = $this->getLoggerDateTime();
            $this->output->writeln("[" . $currentTime . "] " . TaskService::ACTION_INPROGRESS . " task execute initialize.");
            $responceArray[] = $this->processExecuteTask(TaskService::ACTION_INPROGRESS);
            return $responceArray;
        } else {
            return [];
        }
    }

    /**
     * This method used to execute the task with param type
     * @return string
     */
    protected function processExecuteTask($param) 
    {
        if (in_array($param, $this->allowedTaskExecute)) {
            $this->filterStatus = [$param];
            $taskList = [];
            $currentTime = $this->getLoggerDateTime();
            $this->output->writeln("[" . $currentTime . "] Get the available task list in " . $param . " status.");
            $taskResult = $this->getAllTask();
            if (isset($taskResult['body']) && 0 < count($taskResult['body']) && isset($taskResult['body']['task-summary'])) {
                if (isset($taskResult['body']['task-summary']['id'])) {
                     $taskList[] = $taskResult['body']['task-summary'];
                } else if (isset($taskResult['body']['task-summary'][0]['id'])) {
                     $taskList = $taskResult['body']['task-summary'];
                }
                if (count($taskList)) {
                    foreach ($taskList as $value) {
                        $this->taskIds = [$value['id']];
                        $currentTime = $this->getLoggerDateTime();
                        $this->output->writeln(sprintf("[%s] Task id: %d Start the execute", $currentTime, $value['id']));
                        try {
                            if (TaskService::ACTION_RESERVED == $param) {
                                $currentTime = $this->getLoggerDateTime();
                                $this->output->writeln(sprintf("[%s] Task id: %d change to %s status", $currentTime, $value['id'],TaskService::ACTION_INPROGRESS));
                                $this->processToUpdateStatus('start');
                            }
                            $currentTime = $this->getLoggerDateTime();
                            $this->output->writeln(sprintf("[%s] Task id: %d change to %s status", $currentTime, $value['id'],TaskService::ACTION_COMPLETE));
                            $this->processToUpdateStatus('complete');
                        } catch (Execuption $e) {
                            $currentTime = $this->getLoggerDateTime();
                            $this->output->writeln(sprintf("[%s] Task id: %d change to %s status", $currentTime, $value['id'],TaskService::ACTION_SUSPEND));
                            $this->processToUpdateStatus('suspend');
                            $currentTime = $this->getLoggerDateTime();
                            $this->output->writeln(sprintf("[%s] Task execute having error due to %s", $currentTime, $e->getMessage()));
                            continue;
                        }
                    }
                } else {
                    $currentTime = $this->getLoggerDateTime();
                    $this->output->writeln("[" . $currentTime . "] Currently no " . $param . " task to process execute");
                    return [];
                }
            } else {
                $currentTime = $this->getLoggerDateTime();
                $this->output->writeln("[" . $currentTime . "] Currently no " . $param . " task to process execute");
                return [];
            }
        } else {
            $this->errorCode = 16;
            $this->errorMessages[$this->errorCode] = "Invalid status provided to executeTask";
            return [];
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
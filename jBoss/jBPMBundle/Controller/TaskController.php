<?php

namespace jBoss\jBPMBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use jBoss\jBPMBundle\Service\TaskService As Task;

class TaskController extends Controller
{
    /**
     * Based on the requested params get the task list from jbpm using API the response the task list as JSON
     * @param posted form data
     * @return JSON response
     */
    public function listAction(Request $request)
    {
        $logger = $this->get('logger');
    	$errorCode = NULL;
    	$returnData = [];
    	$errorMessages = [];
    	$errorMsg = "";
    	$responseData = [];

    	try{
    		$requestData = $request->request->all();
            if (isset($requestData['status'])) {
                $requestData['filterStatus'] = $requestData['status'];
                unset($requestData['status']);
            }

    		$taskObj = new Task();
            $logger->alert('Task:Controller:listAction before call SetData:', $requestData);
    		if ($taskObj->setData($requestData)) {
                $logger->alert('Task:Controller:listAction before call getAllTask:');
    			$responseData = $taskObj->getAllTask();
                $logger->alert('Task:Controller:listAction getAllTask Response:', $responseData);
    		}

    		$errorCode = $taskObj->getErrorCode();
    		$errorMessages = $taskObj->getErrorMsgs();
			$returnData['data']  = $responseData;
			$errorMsg = implode(',', $errorMessages);
			$returnData['error'] = ['code' => $errorCode, 'message' => $errorMsg];
    	} catch (Exception $e) {
    		$errorCode = 1;
			$errorMessages[] = $e->getMessage();
			$errorMsg = implode(',', $errorMessages);
    		$returnData['error'] = ['code' => $errorCode, 'message' => $errorMsg ];
            $logger->alert('Task:Controller:listAction having error:' . $errorMsg);
    	}

        $logger->alert('Task:Controller:listAction returnData:', $returnData);
		$response = new JsonResponse();
		$response->setData($returnData);
		$response->setStatusCode(JsonResponse::HTTP_OK);

		return $response;
    }

    /**
     * this method used to update the status of the task
     * @param posted form data
     * @return JSON response
     */
    public function updateStatusAction(Request $request)
    {
        $logger = $this->get('logger');
        $errorCode = NULL;
        $returnData = [];
        $errorMessages = [];
        $errorMsg = "";
        $responseData = [];

        try{
            $requestData = $request->request->all();
            $taskObj = new Task();
            $logger->alert('Task:Controller:updateStatusAction before call SetData:', $requestData);
            if ($taskObj->setData($requestData)) {
                $logger->alert('Task:Controller:updateStatusAction before call updateTaskStatus:');
                $responseData = $taskObj->updateTaskStatus();
                if (!is_array($responseData)) {
                    $responseData = [$responseData];
                }
                $logger->alert('Task:Controller:updateStatusAction updateTaskStatus Response:', $responseData);
            }

            $errorCode = $taskObj->getErrorCode();
            $errorMessages = $taskObj->getErrorMsgs();
            $returnData['data']  = $responseData;
            $errorMsg = implode(',', $errorMessages);
            $returnData['error'] = ['code' => $errorCode, 'message' => $errorMsg];
        } catch (Exception $e) {
            $errorCode = 1;
            $errorMessages[] = $e->getMessage();
            $errorMsg = implode(',', $errorMessages);
            $returnData['error'] = ['code' => $errorCode, 'message' => $errorMsg ];
            $logger->alert('Task:Controller:updateStatusAction having error:' . $errorMsg);
        }

        $logger->alert('Task:Controller:updateStatusAction returnData:', $returnData);
        $response = new JsonResponse();
        $response->setData($returnData);
        $response->setStatusCode(JsonResponse::HTTP_OK);

        return $response;
    }
}
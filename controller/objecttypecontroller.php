<?php
/**
 * Copyright (c) 2013 Georg Ehrke <oc.list@georgehrke.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCA\Calendar\Controller;

use \OCA\Calendar\AppFramework\Controller\Controller;
use \OCA\Calendar\AppFramework\Core\API;
use \OCA\Calendar\AppFramework\Http\Http;
use \OCA\Calendar\AppFramework\Http\Request;
use \OCA\Calendar\AppFramework\Http\JSONResponse;

use OCA\Calendar\BusinessLayer\ObjectBusinessLayer;
use \OCA\Calendar\BusinessLayer\BusinessLayerException;

use OCA\Calendar\Db\ObjectType;
use OCA\Calendar\Db\JSONObject;

abstract class ObjectTypeController extends ObjectController {

	private $objectType;

	/**
	 * @param Request $request: an instance of the request
	 * @param API $api: an api wrapper instance
	 * @param BusinessLayer $businessLayer: a businessLayer instance
	 * @param string $objectType: itemtype of wanted elements, use OCA\Calendar\Db\ObjectType::...
	 */
	public function __construct(API $api, Request $request,
								ObjectBusinessLayer $businessLayer, $type){
		parent::__construct($api, $request, $businessLayer);
		$this->objectType = $type;
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @CSRFExemption
	 * @API
	 */
	public function index() {
		$userId 	= $this->api->getUserId();
		$calendarId = $this->params('calendarId');
		$type 		= $this->objectType;
		$limit		= $this->params('limit');
		$offset		= $this->params('offset');
		$expand		= $this->params('expand');
		$start		= $this->params('start');
		$end		= $this->params('end');

		try {
			$this->parseBooleanString($expand);
			$this->parseDateTimeString($start);
			$this->parseDateTimeString($end);

			if($start === null || $end === null) {
				public function findAllByType($calendarId, $type, $userId, $limit = null, $offset = null) {
				$objects = $this->objectBusinessLayer->findAllByType($calendarId, $type, $userId, $limit, $offset);
			} else {
				$objects = $this->objectBusinessLayer->findAllByTypeInPeriod($calendarId, $type, $start, $end, $userId, $limit, $offset);
			}

			if($expand === true) {
				foreach($objects as $object) {
					$expandedObjects = $object->expand($start, $end);
					foreach($expandedObjects as $expandedObject) {
						$jsonObjects = array_merge($jsonObjects, new JSONObject($expandedObject));
					}
				}
			} else {
				foreach($objects as $object) {
					$jsonObjects[] = new JSONObject($object);
				}
			}

			return new JSONResponse($jsonObjects);
		} catch (BusinessLayerException $ex) {
			$this->api->log($ex->getMessage(), 'warn');
			$msg = $this->api->isDebug() ? array('message' => $ex->getMessage()) : array();
			return new JSONResponse($msg, Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @CSRFExemption
	 * @API
	 */
	public function show() {
		$userId 	= $this->api->getUserId();
		$calendarId = $this->params('calendarId');
		$type 		= $this->objectType;
		$limit		= $this->params('limit');
		$offset		= $this->params('offset');
		$expand		= $this->params('expand');
		$start		= $this->params('start');
		$end		= $this->params('end');

		list($routeApp, $routeController, $routeMethod) = explode('.', $this->params('_route'));
		$objectId = $this->params(substr($routeController, 0, strlen($routeController) - 1) . 'Id');

		try {
			$object = $this->objectBusinessLayer->findByType($calendarId, $objectId, $type, $userId);

			$jsonObject = new JSONObject($object);

			return new JSONResponse($jsonObject);
		} catch (BusinessLayerException $ex) {
			$this->api->log($ex->getMessage(), 'warn');
			$msg = $this->api->isDebug() ? array('message' => $ex->getMessage()) : array();
			return new JSONResponse($msg, Http::STATUS_NOT_FOUND);
		}
	}
}
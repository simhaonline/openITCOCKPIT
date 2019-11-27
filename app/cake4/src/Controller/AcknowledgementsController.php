<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AcknowledgedHostConditions;
use itnovum\openITCOCKPIT\Core\AcknowledgedServiceConditions;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\AcknowledgementsControllerRequest;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\Views\BBCodeParser;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use Statusengine2Module\Model\Entity\AcknowledgementHost;

/**
 * Class AcknowledgementsController
 * @property AppPaginatorComponent $Paginator
 * @property AppAuthComponent $Auth
 * @property DbBackend $DbBackend
 */
class AcknowledgementsController extends AppController {

    public $layout = 'blank';

    /**
     * @param int|null $id
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    public function host($id = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship html template
            return;
        }

        session_write_close();

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HostsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid host'));
        }

        /** @var \App\Model\Entity\Host $host */
        $host = $HostsTable->getHostByIdForPermissionCheck($id);
        if (!$this->allowedByContainerId($host->getContainerIds(), false)) {
            $this->render403();
            return;
        }

        $AngularAcknowledgementsControllerRequest = new AcknowledgementsControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this->Paginator, $this, $this->isScrollRequest(), $AngularAcknowledgementsControllerRequest->getPage());

        //Process conditions
        $Conditions = new AcknowledgedHostConditions();
        $Conditions->setFrom($AngularAcknowledgementsControllerRequest->getFrom());
        $Conditions->setTo($AngularAcknowledgementsControllerRequest->getTo());
        $Conditions->setStates($AngularAcknowledgementsControllerRequest->getHostStates());
        $Conditions->setOrder($AngularAcknowledgementsControllerRequest->getOrderForPaginator('AcknowledgementHosts.entry_time', 'desc'));
        $Conditions->setConditions($AngularAcknowledgementsControllerRequest->getHostFilters());
        $Conditions->setHostUuid($host->get('uuid'));


        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->Auth);
        $UserTime = $User->getUserTime();

        $AcknowledgementHostsTable = $this->DbBackend->getAcknowledgementHostsTable();

        //Query acknowledgements records
        $BBCodeParser = new BBCodeParser();
        $all_acknowledgements = [];
        foreach ($AcknowledgementHostsTable->getAcknowledgements($Conditions, $PaginateOMat) as $AcknowledgementHost) {
            /** @var AcknowledgementHost $acknowledgement */
            $Acknowledgement = new \itnovum\openITCOCKPIT\Core\Views\AcknowledgementHost($AcknowledgementHost->toArray(), $UserTime);

            $acknowledgementArray = $Acknowledgement->toArray();
            $acknowledgementArray['comment_data'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($acknowledgementArray['comment_data'], true));

            $all_acknowledgements[] = [
                'AcknowledgedHost' => $acknowledgementArray
            ];
        }

        $this->set('all_acknowledgements', $all_acknowledgements);
        $toJson = ['all_acknowledgements', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_acknowledgements', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param int|null $id
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    public function service($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        session_write_close();

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service'));
        }


        $service = $ServicesTable->getServiceByIdForPermissionsCheck($id);
        if (!$this->allowedByContainerId($service->getContainerIds(), false)) {
            $this->render403();
            return;
        }

        $AngularAcknowledgementsControllerRequest = new AcknowledgementsControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this->Paginator, $this, $this->isScrollRequest(), $AngularAcknowledgementsControllerRequest->getPage());

        //Process conditions
        $Conditions = new AcknowledgedServiceConditions();
        $Conditions->setFrom($AngularAcknowledgementsControllerRequest->getFrom());
        $Conditions->setTo($AngularAcknowledgementsControllerRequest->getTo());
        $Conditions->setStates($AngularAcknowledgementsControllerRequest->getServiceStates());
        $Conditions->setOrder($AngularAcknowledgementsControllerRequest->getOrderForPaginator('AcknowledgementServices.entry_time', 'desc'));
        $Conditions->setConditions($AngularAcknowledgementsControllerRequest->getServiceFilters());
        $Conditions->setServiceUuid($service->get('uuid'));

        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->Auth);
        $UserTime = $User->getUserTime();

        $AcknowledgementServicesTable = $this->DbBackend->getAcknowledgementServicesTable();

        //Query acknowledgements records
        $BBCodeParser = new BBCodeParser();
        $all_acknowledgements = [];
        foreach ($AcknowledgementServicesTable->getAcknowledgements($Conditions, $PaginateOMat) as $acknowledgement) {
            $AcknowledgedService = new itnovum\openITCOCKPIT\Core\Views\AcknowledgementService($acknowledgement, $UserTime);
            $acknowledgementArray = $AcknowledgedService->toArray();
            $acknowledgementArray['comment_data'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($acknowledgementArray['comment_data'], true));
            $all_acknowledgements[] = [
                'AcknowledgedService' => $acknowledgementArray
            ];
        }

        $this->set('all_acknowledgements', $all_acknowledgements);
        $toJson = ['all_acknowledgements', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_acknowledgements', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }
}

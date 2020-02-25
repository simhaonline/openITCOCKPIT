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

namespace MapModule\Model\Table;

use App\Lib\Interfaces\HoststatusTableInterface;
use App\Lib\Interfaces\ServicestatusTableInterface;
use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Entity\Host;
use App\Model\Entity\Service;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicegroupsTable;
use App\Model\Table\ServicesTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\FileDebugger;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\MapConditions;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusConditions;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\Views\BBCodeParser;
use itnovum\openITCOCKPIT\Core\Views\ServiceStateSummary;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\MapFilter;
use MapModule\Model\Entity\Map;
use Statusengine\PerfdataParser;

/**
 * Maps Model
 *
 * @property MapgadgetsTable&HasMany $Mapgadgets
 * @property MapiconsTable&HasMany $Mapicons
 * @property MapitemsTable&HasMany $Mapitems
 * @property MaplinesTable&HasMany $Maplines
 * @property ContainersTable&HasMany $MapsToContainers
 * @property RotationsTable&HasMany $MapsToRotations
 * @property MapsummaryitemsTable&HasMany $Mapsummaryitems
 * @property MaptextsTable&HasMany $Maptexts
 *
 * @method Map get($primaryKey, $options = [])
 * @method Map newEntity($data = null, array $options = [])
 * @method Map[] newEntities(array $data, array $options = [])
 * @method Map|false save(EntityInterface $entity, $options = [])
 * @method Map saveOrFail(EntityInterface $entity, $options = [])
 * @method Map patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Map[] patchEntities($entities, array $data, array $options = [])
 * @method Map findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin TimestampBehavior
 */
class MapsTable extends Table {

    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;
    use CustomValidationTrait;

    private $hostIcons = [
        0 => 'up.png',
        1 => 'down.png',
        2 => 'unreachable.png'
    ];
    private $serviceIcons = [
        0 => 'up.png',
        1 => 'warning.png',
        2 => 'critical.png',
        3 => 'unknown.png'
    ];
    private $ackIcon = 'ack.png';
    private $downtimeIcon = 'downtime.png';
    private $ackAndDowntimeIcon = 'downtime_ack.png';

    private $errorIcon = 'error.png';

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('maps');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsToMany('Containers', [
            'className'        => 'Containers',
            'foreignKey'       => 'map_id',
            'targetForeignKey' => 'container_id',
            'joinTable'        => 'maps_to_containers',
            //'saveStrategy'     => 'replace'
        ]);

        $this->hasMany('Mapgadgets', [
            'foreignKey' => 'map_id',
            'className'  => 'MapModule.Mapgadgets',
            'dependent'  => true
        ]);
        $this->hasMany('Mapicons', [
            'foreignKey' => 'map_id',
            'className'  => 'MapModule.Mapicons',
            'dependent'  => true
        ]);
        $this->hasMany('Mapitems', [
            'foreignKey' => 'map_id',
            'className'  => 'MapModule.Mapitems',
            'dependent'  => true
        ]);
        $this->hasMany('Maplines', [
            'foreignKey' => 'map_id',
            'className'  => 'MapModule.Maplines',
            'dependent'  => true
        ]);
        $this->hasMany('MapsToRotations', [
            'foreignKey' => 'map_id',
            'className'  => 'MapModule.MapsToRotations',
            'dependent'  => true
        ]);
        $this->hasMany('Mapsummaryitems', [
            'foreignKey' => 'map_id',
            'className'  => 'MapModule.Mapsummaryitems',
            'dependent'  => true
        ]);
        $this->hasMany('Maptexts', [
            'foreignKey' => 'map_id',
            'className'  => 'MapModule.Maptexts',
            'dependent'  => true
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->requirePresence('containers', true, __('You have to choose at least one option.'))
            ->allowEmptyString('containers', null, false)
            ->multipleOptions('containers', [
                'min' => 1
            ], __('You have to choose at least one option.'));

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('background')
            ->maxLength('background', 128)
            ->allowEmptyString('background');

        $validator
            ->integer('refresh_interval')
            ->notEmptyString('refresh_interval');

        return $validator;
    }

    /**
     * @param MapFilter $MapFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMapsIndex(MapFilter $MapFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }
        $query = $this->find('all')
            ->where($MapFilter->indexFilter())
            ->distinct('Maps.id')
            ->contain(['Containers'])
            ->innerJoinWith('Containers', function (Query $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })
            ->order($MapFilter->getOrderForPaginator('Maps.name', 'asc'));

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
            }
        }

        return $result;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Maps.id' => $id]);
    }

    /**
     * @param $realMapId
     * @param $mapItemMapId
     * @return array
     */
    public function getMapsForMaps($realMapId, $mapItemMapId) {
        $query = $this->find()
            ->contain(['Containers'])
            ->join([
                'table'      => 'mapitems',
                'type'       => 'INNER',
                'alias'      => 'Mapitems',
                'conditions' => 'Mapitems.object_id = Maps.id',
            ])
            ->where([
                'Maps.id'         => $realMapId,
                'Mapitems.map_id' => $mapItemMapId,
            ]);

        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $query->toArray();
    }

    /**
     * @param $realMapId
     * @param $mapItemMapId
     * @return array
     */
    public function getMapsForMapsummaryitems($realMapId, $mapItemMapId) {
        $query = $this->find()
            ->contain(['Containers'])
            ->join([
                'table'      => 'mapsummaryitems',
                'type'       => 'INNER',
                'alias'      => 'Mapsummaryitems',
                'conditions' => 'Mapsummaryitems.object_id = Maps.id',
            ])
            ->where([
                'Maps.id'                => $realMapId,
                'Mapsummaryitems.map_id' => $mapItemMapId,
            ]);

        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $query->toArray();
    }

    /**
     * @param $object_id
     * @return array
     */
    public function getMapsummaryitemForMapsummary($object_id) {
        $query = $this->find()
            ->contain(['Containers'])
            ->join([
                'table'      => 'mapsummaryitems',
                'type'       => 'INNER',
                'alias'      => 'Mapsummaryitems',
                'conditions' => 'Mapsummaryitems.object_id = Maps.id',
            ])
            ->select([
                'Mapsummaryitems.object_id',
            ])
            ->where([
                'Mapsummaryitems.object_id' => $object_id,
            ])->enableAutoFields(true);

        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $query->toArray();
    }

    /**
     * @param $object_id
     * @return array
     */
    public function getMapitemForMapsummary($object_id) {
        $query = $this->find()
            ->contain(['Containers'])
            ->join([
                'table'      => 'mapitems',
                'type'       => 'INNER',
                'alias'      => 'Mapitems',
                'conditions' => 'Mapitems.object_id = Maps.id',
            ])
            ->select([
                'Mapitems.object_id',
            ])
            ->where([
                'Mapitems.object_id' => $object_id,
            ])->enableAutoFields(true);

        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $query->toArray();
    }

    /**
     * @param int $id
     * @return array
     */
    public function getMapForEdit($id) {
        $query = $this->find()
            ->where([
                'Maps.id' => $id
            ])
            ->contain([
                'Containers'
            ])
            ->disableHydration()
            ->first();


        $contact = $query;
        $contact['containers'] = [
            '_ids' => Hash::extract($query, 'containers.{n}.id')
        ];

        return [
            'Map' => $contact
        ];
    }

    /**
     * @param array $ids
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMapsForCopy($ids = [], $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        $query = $this->find()
            ->select([
                'Maps.id',
                'Maps.name',
                'Maps.title',
                'Maps.refresh_interval'
            ])
            ->where(['Maps.id IN' => $ids])
            ->order(['Maps.id' => 'asc'])
            ->contain(['Containers'])
            ->innerJoinWith('Containers', function (Query $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })
            ->group(['Maps.id'])
            ->disableHydration()
            ->all();

        return $query->toArray();
    }

    /**
     * @param MapConditions $MapConditions
     * @param array $selected
     * @param array $excluded
     * @return array|null
     */
    public function getMapsForAngular(MapConditions $MapConditions, $selected = [], $excluded = []) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }
        $query = $this->find('list')
            ->limit(ITN_AJAX_LIMIT)
            ->select([
                'Maps.id',
                'Maps.name'
            ])
            ->join([
                'table'      => 'maps_to_containers',
                'alias'      => 'MapsToContainers',
                'type'       => 'INNER',
                'conditions' => [
                    'MapsToContainers.map_id = Maps.id',
                ]
            ])->where($MapConditions->getConditionsForFind());

        $selected = array_filter($selected);
        if (!empty($selected)) {
            $query->where([
                'Maps.id NOT IN' => $selected
            ]);
        }

        $query->order(['Maps.name' => 'ASC'])->group('Maps.id');
        $mapsWithLimit = $query->toArray();

        $selectedMaps = [];
        if (!empty($selected)) {
            $query = $this->find('list')
                ->select([
                    'Maps.id',
                    'Maps.name'
                ])
                ->where([
                    'Maps.id IN' => $selected
                ])
                ->join([
                    'table'      => 'maps_to_containers',
                    'alias'      => 'MapsToContainers',
                    'type'       => 'INNER',
                    'conditions' => [
                        'MapsToContainers.map_id = Maps.id',
                    ]
                ]);

            if ($MapConditions->hasContainer()) {
                $query->where([
                    'MapsToContainers.container_id IN' => $MapConditions->getContainerIds()
                ]);
            }
            $query->orderAsc('Maps.name')->groupBy('Maps.id');
            $selectedMaps = $query->toArray();
        }
        $maps = $mapsWithLimit + $selectedMaps;
        if (is_array($excluded) && !empty($excluded)) {
            foreach ($excluded as $idToExclude) {
                if (isset($maps[$idToExclude])) {
                    unset($maps[$idToExclude]);
                }
            }
        }
        asort($maps, SORT_FLAG_CASE | SORT_NATURAL);
        return $maps;
    }

    /**
     * @param array $conditions
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMapsForRotations($conditions = [], $MY_RIGHTS = []) {
        $query = $this->find()
            ->join([
                'table'      => 'maps_to_containers',
                'alias'      => 'MapsToContainers',
                'type'       => 'INNER',
                'conditions' => [
                    'MapsToContainers.map_id = Maps.id',
                ]
            ])
            ->select([
                'Maps.id',
                'Maps.name',
                'MapsToContainers.map_id',
                'MapsToContainers.container_id'
            ]);

        if (!empty($conditions)) {
            $query->where($conditions);
        }
        if (!empty($MY_RIGHTS)) {
            $query->where([
                'MapsToContainers.container_id IN' => $MY_RIGHTS
            ]);
        }

        $result = $query->all();
        if (empty($result)) {
            return [];
        }
        return $query->toArray();
    }

    /**
     * @param ServicesTable $Service
     * @param HoststatusTableInterface $Hoststatus
     * @param ServicestatusTableInterface $Servicestatus
     * @param Host $host
     * @return array
     */
    public function getHostInformation(ServicesTable $Service, HoststatusTableInterface $Hoststatus, ServicestatusTableInterface $Servicestatus, Host $host) {
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
        $hoststatus = $Hoststatus->byUuid($host->get('uuid'), $HoststatusFields);
        $HostView = new \itnovum\openITCOCKPIT\Core\Views\Host($host->toArray());

        if (empty($hoststatus) || $host->get('disabled')) {
            $HoststatusView = new \itnovum\openITCOCKPIT\Core\Hoststatus([]);
            return [
                'icon'           => $this->errorIcon,
                'icon_property'  => $this->errorIcon,
                'isAcknowledged' => false,
                'isInDowntime'   => false,
                'color'          => 'text-primary',
                'background'     => 'bg-color-blueLight',
                'Host'           => $HostView->toArray(),
                'Hoststatus'     => $HoststatusView->toArray(),
            ];
        }

        $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($hoststatus['Hoststatus']);
        $icon = $this->hostIcons[$hoststatus->currentState()];
        $color = $hoststatus->HostStatusColor();
        $background = $hoststatus->HostStatusBackgroundColor();

        $iconProperty = $icon;
        if ($hoststatus->isAcknowledged()) {
            $iconProperty = $this->ackIcon;
        }

        if ($hoststatus->isInDowntime()) {
            $iconProperty = $this->downtimeIcon;
        }

        if ($hoststatus->isAcknowledged() && $hoststatus->isInDowntime()) {
            $iconProperty = $this->ackAndDowntimeIcon;
        }

        if ($hoststatus->currentState() > 0) {
            return [
                'icon'           => $icon,
                'icon_property'  => $this->errorIcon,
                'isAcknowledged' => $hoststatus->isAcknowledged(),
                'isInDowntime'   => $hoststatus->isInDowntime(),
                'color'          => $color,
                'background'     => $background,
                'Host'           => $HostView->toArray(),
                'Hoststatus'     => $hoststatus->toArray(),
            ];
        }

        //Check services for cumulated state (only if host is up)

        $services = $Service->getActiveServicesByHostId($host->get('id'), false);
        $services = $services->toArray();
        $serviceUuids = Hash::extract($services, '{n}.uuid');
        $servicestatus = [];

        if (!empty($serviceUuids)) {
            $ServicestatusFieds = new ServicestatusFields(new DbBackend());
            $ServicestatusFieds->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
            $ServicestatusConditions = new ServicestatusConditions(new DbBackend());
            $ServicestatusConditions->servicesWarningCriticalAndUnknown();
            $servicestatus = $Servicestatus->byUuid($serviceUuids, $ServicestatusFieds, $ServicestatusConditions);
        }

        if (!empty($servicestatus)) {
            $worstServiceState = array_values(
                Hash::sort($servicestatus, '{s}.Servicestatus.current_state', 'desc')
            );

            $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($worstServiceState[0]['Servicestatus']);
            $serviceIcon = $this->serviceIcons[$servicestatus->currentState()];

            $serviceIconProperty = $serviceIcon;
            if ($servicestatus->isAcknowledged()) {
                $serviceIconProperty = $this->ackIcon;
            }

            if ($servicestatus->isInDowntime()) {
                $serviceIconProperty = $this->downtimeIcon;
            }

            if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
                $serviceIconProperty = $this->ackAndDowntimeIcon;
            }

            return [
                'icon'           => $serviceIcon,
                'icon_property'  => $serviceIconProperty,
                'isAcknowledged' => $servicestatus->isAcknowledged(),
                'isInDowntime'   => $servicestatus->isInDowntime(),
                'color'          => $servicestatus->ServiceStatusColor(),
                'background'     => $servicestatus->ServiceStatusBackgroundColor(),
                'Host'           => $HostView->toArray(),
                'Hoststatus'     => $hoststatus->toArray(),
            ];
        }

        return [
            'icon'           => $icon,
            'icon_property'  => $iconProperty,
            'isAcknowledged' => $hoststatus->isAcknowledged(),
            'isInDowntime'   => $hoststatus->isInDowntime(),
            'color'          => $color,
            'background'     => $background,
            'Host'           => $HostView->toArray(),
            'Hoststatus'     => $hoststatus->toArray()
        ];
    }

    /**
     * @param ServicestatusTableInterface $Servicestatus
     * @param Service $service
     * @param bool $includeServiceOutput
     * @return array
     */
    public function getServiceInformation(ServicestatusTableInterface $Servicestatus, Service $service, $includeServiceOutput = false) {
        $ServicestatusFields = new ServicestatusFields(new DbBackend());
        $ServicestatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged()->perfdata()->isFlapping();
        if ($includeServiceOutput === true) {
            $ServicestatusFields->output()->longOutput();
        }
        $serviceArray = $service->toArray();
        $servicestatus = $Servicestatus->byUuid($service->get('uuid'), $ServicestatusFields);
        $HostView = new \itnovum\openITCOCKPIT\Core\Views\Host($serviceArray['host']);
        $ServiceView = new \itnovum\openITCOCKPIT\Core\Views\Service($serviceArray);
        if (empty($servicestatus) || $service->get('disabled')) {
            $ServicestatusView = new \itnovum\openITCOCKPIT\Core\Servicestatus([]);
            $tmpServicestatus = $ServicestatusView->toArray();
            if ($includeServiceOutput === true) {
                $tmpServicestatus['output'] = null;
                $tmpServicestatus['longOutputHtml'] = null;
            }

            return [
                'icon'           => $this->errorIcon,
                'icon_property'  => $this->errorIcon,
                'isAcknowledged' => false,
                'isInDowntime'   => false,
                'color'          => 'text-primary',
                'background'     => 'bg-color-blueLight',
                'Host'           => $HostView->toArray(),
                'Service'        => $ServiceView->toArray(),
                'Servicestatus'  => $tmpServicestatus,
                'Perfdata'       => []
            ];
        }

        $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($servicestatus['Servicestatus']);

        $icon = $this->serviceIcons[$servicestatus->currentState()];

        $iconProperty = $icon;
        if ($servicestatus->isAcknowledged()) {
            $iconProperty = $this->ackIcon;
        }

        if ($servicestatus->isInDowntime()) {
            $iconProperty = $this->downtimeIcon;
        }

        if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
            $iconProperty = $this->ackAndDowntimeIcon;
        }

        $perfdata = new PerfdataParser($servicestatus->getPerfdata());

        $tmpServicestatus = $servicestatus->toArray();
        if ($includeServiceOutput === true) {
            $Parser = new BBCodeParser();
            $tmpServicestatus['output'] = h($servicestatus->getOutput());
            $tmpServicestatus['longOutputHtml'] = $Parser->nagiosNl2br($Parser->asHtml($servicestatus->getLongOutput(), true));
        }

        return [
            'icon'           => $icon,
            'icon_property'  => $iconProperty,
            'isAcknowledged' => $servicestatus->isAcknowledged(),
            'isInDowntime'   => $servicestatus->isInDowntime(),
            'color'          => $servicestatus->ServiceStatusColor(),
            'background'     => $servicestatus->ServiceStatusBackgroundColor(),
            'Host'           => $HostView->toArray(),
            'Service'        => $ServiceView->toArray(),
            'Perfdata'       => $perfdata->parse(),
            'Servicestatus'  => $tmpServicestatus
        ];
    }

    /**
     * @param ServicesTable $Service
     * @param array $hostgroup
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @return array
     */
    public function getHostgroupInformation(ServicesTable $Service, array $hostgroup, HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $ServicestatusTable) {
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();

        $hostUuids = \Cake\Utility\Hash::extract($hostgroup['hosts'], '{n}.uuid');

        $hoststatusByUuids = $HoststatusTable->byUuid($hostUuids, $HoststatusFields);
        $hostgroupLight = [
            'id'          => (int)$hostgroup['id'],
            'name'        => $hostgroup['Containers']['name'],
            'description' => $hostgroup['description']
        ];

        if (empty($hoststatusByUuids)) {
            return [
                'icon'       => $this->errorIcon,
                'color'      => 'text-primary',
                'background' => 'bg-color-blueLight',
                'Hostgroup'  => $hostgroupLight
            ];
        }
        $worstHostState = array_values(
            Hash::sort($hoststatusByUuids, '{s}.Hoststatus.current_state', 'desc')
        );

        $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($worstHostState[0]['Hoststatus']);

        $icon = $this->hostIcons[$hoststatus->currentState()];
        $color = $hoststatus->HostStatusColor();
        $background = $hoststatus->HostStatusBackgroundColor();


        if ($hoststatus->isAcknowledged()) {
            $icon = $this->ackIcon;
        }

        if ($hoststatus->isInDowntime()) {
            $icon = $this->downtimeIcon;
        }

        if ($hoststatus->isAcknowledged() && $hoststatus->isInDowntime()) {
            $icon = $this->ackAndDowntimeIcon;
        }

        if ($hoststatus->currentState() > 0) {
            return [
                'icon'       => $icon,
                'color'      => $color,
                'background' => $background,
                'Hostgroup'  => $hostgroupLight
            ];
        }

        //Check services for cumulated state (only if host is up)
        $hostIds = \Cake\Utility\Hash::extract($hostgroup['hosts'], '{n}.id');

        //Check services for cumulated state (only if host is up)
        $services = $Service->getActiveServicesByHostIds($hostIds, false);
        $services = $services->toArray();
        $servicestatus = [];

        if (!empty($services)) {
            $ServicestatusFieds = new ServicestatusFields(new DbBackend());
            $ServicestatusFieds->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
            $ServicestatusConditions = new ServicestatusConditions(new DbBackend());
            $ServicestatusConditions->servicesWarningCriticalAndUnknown();
            $servicestatus = $ServicestatusTable->byUuid(Hash::extract($services, '{n}.uuid'), $ServicestatusFieds, $ServicestatusConditions);
        }

        if (!empty($servicestatus)) {
            $worstServiceState = array_values(
                Hash::sort($servicestatus, '{s}.Servicestatus.current_state', 'desc')
            );

            $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($worstServiceState[0]['Servicestatus']);
            $serviceIcon = $this->serviceIcons[$servicestatus->currentState()];

            if ($servicestatus->isAcknowledged()) {
                $serviceIcon = $this->ackIcon;
            }

            if ($servicestatus->isInDowntime()) {
                $serviceIcon = $this->downtimeIcon;
            }

            if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
                $serviceIcon = $this->ackAndDowntimeIcon;
            }
            return [
                'icon'       => $serviceIcon,
                'color'      => $servicestatus->ServiceStatusColor(),
                'background' => $servicestatus->ServiceStatusBackgroundColor(),
                'Hostgroup'  => $hostgroupLight
            ];
        }

        return [
            'icon'       => $icon,
            'color'      => $color,
            'background' => $background,
            'Hostgroup'  => $hostgroupLight
        ];
    }

    /**
     * @param ServicesTable $Service
     * @param ServicestatusTableInterface $Servicestatus
     * @param array $servicegroup
     * @return array
     */
    public function getServicegroupInformation(ServicesTable $Service, ServicestatusTableInterface $Servicestatus, $servicegroup = []) {
        $ServicestatusFields = new ServicestatusFields(new DbBackend());
        $ServicestatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();

        $serviceUuids = Hash::extract($servicegroup['services'], '{n}.uuid');

        $servicestatusByUuids = [];
        if (!empty($serviceUuids)) {
            $servicestatusByUuids = $Servicestatus->byUuid($serviceUuids, $ServicestatusFields);
        }

        $servicegroupLight = [
            'id'          => (int)$servicegroup['id'],
            'name'        => $servicegroup['container']['name'],
            'description' => $servicegroup['description']
        ];

        if (empty($servicestatusByUuids)) {
            return [
                'icon'         => $this->errorIcon,
                'color'        => 'text-primary',
                'background'   => 'bg-color-blueLight',
                'Servicegroup' => $servicegroupLight
            ];
        }
        $worstServiceState = array_values(
            Hash::sort($servicestatusByUuids, '{s}.Servicestatus.current_state', 'desc')
        );

        $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($worstServiceState[0]['Servicestatus']);

        $icon = $this->serviceIcons[$servicestatus->currentState()];
        $color = $servicestatus->ServiceStatusColor();
        $background = $servicestatus->ServiceStatusBackgroundColor();


        if ($servicestatus->isAcknowledged()) {
            $icon = $this->ackIcon;
        }

        if ($servicestatus->isInDowntime()) {
            $icon = $this->downtimeIcon;
        }

        if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
            $icon = $this->ackAndDowntimeIcon;
        }

        return [
            'icon'         => $icon,
            'color'        => $color,
            'background'   => $background,
            'Servicegroup' => $servicegroupLight
        ];
    }

    /**
     * @param $dependentMapsIds
     * @param HostgroupsTable $HostgroupsTable
     * @param ServicegroupsTable $ServicegroupsTable
     * @return array
     */
    public function getAllDependentMapsElements($dependentMapsIds, HostgroupsTable $HostgroupsTable, ServicegroupsTable $ServicegroupsTable, $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }
        if (!is_array($dependentMapsIds)) {
            $dependentMapsIds = [$dependentMapsIds];
        }

        $allDependentMapElements = $this->find()
            ->contain([
                'Mapitems'        => function (Query $query) {
                    $query->select([
                        'Mapitems.type',
                        'Mapitems.object_id',
                        'Mapitems.map_id'
                    ])->where([
                        'Mapitems.type !=' => 'map'
                    ]);
                    return $query;
                },
                'Maplines'        => function (Query $query) {
                    $query->select([
                        'Maplines.type',
                        'Maplines.object_id',
                        'Maplines.map_id'
                    ])->where([
                        'Maplines.type !=' => 'stateless'
                    ]);
                    return $query;
                },
                'Mapgadgets'      => function (Query $query) {
                    $query->select([
                        'Mapgadgets.type',
                        'Mapgadgets.object_id',
                        'Mapgadgets.map_id'
                    ]);
                    return $query;
                },
                'Mapsummaryitems' => function (Query $query) {
                    $query->select([
                        'Mapsummaryitems.type',
                        'Mapsummaryitems.object_id',
                        'Mapsummaryitems.map_id'
                    ])->where([
                        'Mapsummaryitems.type !=' => 'map'
                    ]);
                    return $query;
                },
            ])
            ->where([
                'Maps.id IN' => $dependentMapsIds
            ])
            ->all()->toArray();

        $mapElementsByCategory = [
            'host'         => [],
            'hostgroup'    => [],
            'service'      => [],
            'servicegroup' => []
        ];
        $allDependentMapElements = Hash::filter($allDependentMapElements);

        foreach ($allDependentMapElements as $allDependentMapElementArray) {
            foreach ($allDependentMapElementArray->toArray() as $mapElementKey => $mapElementData) {
                if ($mapElementKey === 'Map') {
                    continue;
                }
                if (is_array($mapElementData)) {
                    foreach ($mapElementData as $mapElement) {
                        $mapElementsByCategory[$mapElement['type']][$mapElement['object_id']] = $mapElement['object_id'];
                    }
                }
            }

        }

        $hostIds = $mapElementsByCategory['host'];
        if (!empty($mapElementsByCategory['hostgroup'])) {
            $query = $HostgroupsTable->find()
                ->join([
                    [
                        'table'      => 'hosts_to_hostgroups',
                        'type'       => 'INNER',
                        'alias'      => 'HostsToHostgroups',
                        'conditions' => 'HostsToHostgroups.hostgroup_id = Hostgroups.id',
                    ]
                ])
                ->select([
                    'HostsToHostgroups.host_id'
                ])
                ->where([
                    'Hostgroups.id IN' => $mapElementsByCategory['hostgroup']
                ]);
            /*
            $query = [
                'recursive'  => -1,
                'joins'      => [
                    [
                        'table'      => 'hosts_to_hostgroups',
                        'type'       => 'INNER',
                        'alias'      => 'HostsToHostgroups',
                        'conditions' => 'HostsToHostgroups.hostgroup_id = Hostgroup.id',
                    ],
                ],
                'fields'     => [
                    'HostsToHostgroups.host_id'
                ],
                'conditions' => [
                    'Hostgroup.id' => $mapElementsByCategory['hostgroup']
                ]
            ];
            */
            if (!empty($MY_RIGHTS)) {
                $query->where([
                    'Hostgroups.container_id IN' => $MY_RIGHTS
                ]);
            }

            $hostIdsByHostgroup = $query->all()->toArray();
            foreach ($hostIdsByHostgroup as $hostIdByHostgroup) {
                $hostIds[$hostIdByHostgroup['HostsToHostgroups']['host_id']] = $hostIdByHostgroup['HostsToHostgroups']['host_id'];
            }
        }
        $serviceIds = $mapElementsByCategory['service'];
        if (!empty($mapElementsByCategory['servicegroup'])) {
            $query = $ServicegroupsTable->find()
                ->join([
                    [
                        'table'      => 'services_to_servicegroups',
                        'type'       => 'INNER',
                        'alias'      => 'ServicesToServicegroups',
                        'conditions' => 'ServicesToServicegroups.servicegroup_id = Servicegroups.id',
                    ]
                ])
                ->select([
                    'ServicesToServicegroups.service_id'
                ])
                ->where([
                    'Servicegroups.id IN' => $mapElementsByCategory['servicegroup']
                ]);
            /*
            $query = [
                'recursive'  => -1,
                'joins'      => [
                    [
                        'table'      => 'services_to_servicegroups',
                        'type'       => 'INNER',
                        'alias'      => 'ServicesToServicegroups',
                        'conditions' => 'ServicesToServicegroups.servicegroup_id = Servicegroup.id',
                    ],
                ],
                'fields'     => [
                    'ServicesToServicegroups.service_id'

                ],
                'conditions' => [
                    'Servicegroup.id' => $mapElementsByCategory['servicegroup']
                ]
            ];
            */
            if (!empty($MY_RIGHTS)) {
                $query->where([
                    'Servicegroups.container_id IN' => $MY_RIGHTS
                ]);
            }

            $serviceIdsByServicegroup = $query->all()->toArray();
            foreach ($serviceIdsByServicegroup as $serviceIdByServicegroup) {
                $serviceIds[$serviceIdByServicegroup['ServicesToServicegroups']['service_id']] = $serviceIdByServicegroup['ServicesToServicegroups']['service_id'];
            }
        }
        return [
            'hostIds'    => $hostIds,
            'serviceIds' => $serviceIds
        ];
    }

    /**
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param array $map
     * @param array $hosts
     * @param array $services
     * @return array
     */
    public function getMapInformation(HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $ServicestatusTable, array $map, array $hosts, array $services) {
        $map = [
            'id'    => $map[0]['id'],
            'name'  => $map[0]['name'],
            'title' => $map[0]['title']
        ];

        if (empty($hosts) && empty($services)) {
            return [
                'icon'       => $this->errorIcon,
                'color'      => 'text-primary',
                'background' => 'bg-color-blueLight',
                'Map'        => $map
            ];
        }

        $hostsUuids = Hash::extract($hosts, '{n}.uuid');
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
        $hoststatusByUuids = $HoststatusTable->byUuid($hostsUuids, $HoststatusFields);

        if (empty($hoststatusByUuids)) {
            $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus([]);
            $icon = $this->errorIcon;
            $color = $hoststatus->HostStatusColor();
            $background = $hoststatus->HostStatusBackgroundColor();
            $iconProperty = $icon;
        } else {
            $worstHostState = array_values(
                Hash::sort($hoststatusByUuids, '{s}.Hoststatus.current_state', 'desc')
            );
            if (!empty($worstHostState)) {
                $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($worstHostState[0]['Hoststatus']);
            }
            $icon = $this->hostIcons[$hoststatus->currentState()];
            $color = $hoststatus->HostStatusColor();
            $background = $hoststatus->HostStatusBackgroundColor();
            $iconProperty = $icon;


            if ($hoststatus->isAcknowledged()) {
                $iconProperty = $this->ackIcon;
            }

            if ($hoststatus->isInDowntime()) {
                $iconProperty = $this->downtimeIcon;
            }

            if ($hoststatus->isAcknowledged() && $hoststatus->isInDowntime()) {
                $iconProperty = $this->ackAndDowntimeIcon;
            }
            if ($hoststatus->currentState() > 0) {
                return [
                    'icon'          => $icon,
                    'icon_property' => $iconProperty,
                    'color'         => $color,
                    'background'    => $background,
                    'Map'           => $map
                ];
            }
        }

        $servicesUuids = Hash::extract($services, '{n}.Service.uuid');
        $ServicestatusFieds = new ServicestatusFields(new DbBackend());
        $ServicestatusFieds->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
        $ServicestatusConditions = new ServicestatusConditions(new DbBackend());
        $ServicestatusConditions->servicesWarningCriticalAndUnknown();
        $servicestatus = $ServicestatusTable->byUuid($servicesUuids, $ServicestatusFieds, $ServicestatusConditions);

        if (!empty($servicestatus)) {
            $worstServiceState = array_values(
                Hash::sort($servicestatus, '{s}.Servicestatus.current_state', 'desc')
            );
            $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($worstServiceState[0]['Servicestatus']);
            $serviceIcon = $this->serviceIcons[$servicestatus->currentState()];

            $serviceIconProperty = $serviceIcon;
            if ($servicestatus->isAcknowledged()) {
                $serviceIconProperty = $this->ackIcon;
            }

            if ($servicestatus->isInDowntime()) {
                $serviceIconProperty = $this->downtimeIcon;
            }

            if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
                $serviceIconProperty = $this->ackAndDowntimeIcon;
            }

            return [
                'icon'           => $serviceIcon,
                'icon_property'  => $serviceIconProperty,
                'isAcknowledged' => $servicestatus->isAcknowledged(),
                'isInDowntime'   => $servicestatus->isInDowntime(),
                'color'          => $servicestatus->ServiceStatusColor(),
                'background'     => $servicestatus->ServiceStatusBackgroundColor(),
                'Map'            => $map,
            ];
        }
        return [
            'icon'           => $icon,
            'icon_property'  => $iconProperty,
            'isAcknowledged' => $hoststatus->isAcknowledged(),
            'isInDowntime'   => $hoststatus->isInDowntime(),
            'color'          => $color,
            'background'     => $background,
            'Map'            => $map
        ];
    }

    /**
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param array $host
     * @return array
     */
    public function getHostInformationForSummaryIcon(HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $ServicestatusTable, array $host) {
        $bitMaskHostState = 0;
        $bitMaskServiceState = 0;
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields->currentState();
        $ServicestatusFields = new ServicestatusFields(new DbBackend());
        $ServicestatusFields->currentState();
        $hoststatus = $HoststatusTable->byUuid($host['uuid'], $HoststatusFields);
        $serviceUuids = Hash::extract($host['services'], '{n}.uuid');
        $servicestatus = $ServicestatusTable->byUuid($serviceUuids, $ServicestatusFields);

        $HostView = new \itnovum\openITCOCKPIT\Core\Views\Host($host);

        if ((empty($hoststatus) && empty($servicestatus)) || $host['disabled']) {
            return [
                'BitMaskHostState'    => $bitMaskHostState,
                'BitMaskServiceState' => $bitMaskServiceState,
                'Host'                => $HostView->toArray(),
            ];
        }
        if (isset($hoststatus['Hoststatus']['current_state'])) {
            $bitMaskHostState = 1 << $hoststatus['Hoststatus']['current_state'];
        }

        foreach ($servicestatus as $statusDetails) {
            $bitMaskServiceState |= 1 << $statusDetails['Servicestatus']['current_state'];
        }
        return [
            'BitMaskHostState'    => $bitMaskHostState,
            'BitMaskServiceState' => $bitMaskServiceState,
            'Host'                => $HostView->toArray(),
        ];
    }

    /**
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param array $service
     * @return array
     */
    public function getServiceInformationForSummaryIcon(HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $ServicestatusTable, array $service) {
        $bitMaskHostState = 0;
        $bitMaskServiceState = 0;
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields->currentState();
        $ServicestatusFields = new ServicestatusFields(new DbBackend());
        $ServicestatusFields->currentState();
        $hoststatus = $HoststatusTable->byUuid($service['host']['uuid'], $HoststatusFields);
        $servicestatus = $ServicestatusTable->byUuid($service['uuid'], $ServicestatusFields);

        $HostView = new \itnovum\openITCOCKPIT\Core\Views\Host($service['host']);
        $ServiceView = new \itnovum\openITCOCKPIT\Core\Views\Service($service);


        if ((empty($hoststatus) && empty($servicestatus)) || $service['disabled']) {
            return [
                'BitMaskHostState'    => $bitMaskHostState,
                'BitMaskServiceState' => $bitMaskServiceState,
                'Host'                => $HostView->toArray(),
                'Service'             => $ServiceView->toArray(),
            ];
        }
        if (isset($hoststatus['Hoststatus']['current_state'])) {
            $bitMaskHostState = 1 << $hoststatus['Hoststatus']['current_state'];
        }

        if (isset($servicestatus['Servicestatus']['current_state'])) {
            $bitMaskServiceState = 1 << $servicestatus['Servicestatus']['current_state'];
        }

        return [
            'BitMaskHostState'    => $bitMaskHostState,
            'BitMaskServiceState' => $bitMaskServiceState,
            'Host'                => $HostView->toArray(),
            'Service'             => $ServiceView->toArray(),
        ];
    }

    /**
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param array $hostgroup
     * @return array
     */
    public function getHostgroupInformationForSummaryIcon(HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $ServicestatusTable, array $hostgroup) {
        $hostgroupLight = [
            'id'          => (int)$hostgroup['id'],
            'name'        => $hostgroup['container']['name'],
            'description' => $hostgroup['description']
        ];
        $bitMaskHostState = 0;
        $bitMaskServiceState = 0;
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields->currentState();
        $ServicestatusFields = new ServicestatusFields(new DbBackend());
        $ServicestatusFields->currentState();
        $hostUuids = Hash::extract($hostgroup['hosts'], '{n}.uuid');
        $serviceUuids = Hash::extract($hostgroup['hosts'], '{n}.services.{n}.uuid');

        $hoststatus = $HoststatusTable->byUuid($hostUuids, $HoststatusFields);
        $servicestatus = $ServicestatusTable->byUuid($serviceUuids, $ServicestatusFields);

        if (empty($hoststatus) && empty($servicestatus)) {
            return [
                'BitMaskHostState'    => $bitMaskHostState,
                'BitMaskServiceState' => $bitMaskServiceState,
                'Hostgroup'           => $hostgroupLight
            ];
        }
        foreach ($hoststatus as $statusDetails) {
            $bitMaskHostState |= 1 << $statusDetails['Hoststatus']['current_state'];
        }
        foreach ($servicestatus as $statusDetails) {
            $bitMaskServiceState |= 1 << $statusDetails['Servicestatus']['current_state'];
        }
        return [
            'BitMaskHostState'    => $bitMaskHostState,
            'BitMaskServiceState' => $bitMaskServiceState,
            'Hostgroup'           => $hostgroupLight
        ];
    }

    /**
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param array $servicegroup
     * @return array
     */
    public function getServicegroupInformationForSummaryIcon(HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $ServicestatusTable, array $servicegroup) {
        $servicegroupLight = [
            'id'          => (int)$servicegroup['id'],
            'name'        => $servicegroup['container']['name'],
            'description' => $servicegroup['description']
        ];
        $bitMaskHostState = 0;
        $bitMaskServiceState = 0;
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields->currentState();
        $ServicestatusFields = new ServicestatusFields(new DbBackend());
        $ServicestatusFields->currentState();
        $hostUuids = Hash::extract($servicegroup['services'], '{n}.host.uuid');
        $serviceUuids = Hash::extract($servicegroup['services'], '{n}.uuid');
        $hoststatus = $HoststatusTable->byUuid($hostUuids, $HoststatusFields);
        $servicestatus = $ServicestatusTable->byUuid($serviceUuids, $ServicestatusFields);

        if (empty($hoststatus) && empty($servicestatus)) {
            return [
                'BitMaskHostState'    => $bitMaskHostState,
                'BitMaskServiceState' => $bitMaskServiceState,
                'Servicegroup'        => $servicegroupLight
            ];
        }
        foreach ($hoststatus as $statusDetails) {
            $bitMaskHostState |= 1 << $statusDetails['Hoststatus']['current_state'];
        }
        foreach ($servicestatus as $statusDetails) {
            $bitMaskServiceState |= 1 << $statusDetails['Servicestatus']['current_state'];
        }
        return [
            'BitMaskHostState'    => $bitMaskHostState,
            'BitMaskServiceState' => $bitMaskServiceState,
            'Servicegroup'        => $servicegroupLight
        ];
    }

    /**
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param array $map
     * @param array $hosts
     * @param array $services
     * @return array
     */
    public function getMapInformationForSummaryIcon(HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $ServicestatusTable, array $map, array $hosts, array $services) {
        $bitMaskHostState = 0;
        $bitMaskServiceState = 0;

        if (empty($hosts) && empty($services)) {
            return [
                'BitMaskHostState'    => $bitMaskHostState,
                'BitMaskServiceState' => $bitMaskServiceState,
                'Map'                 => $map
            ];
        }
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields->currentState();
        $ServicestatusFields = new ServicestatusFields(new DbBackend());
        $ServicestatusFields->currentState();
        $hostsUuids = Hash::extract($hosts, '{n}.uuid');
        $servicesUuids = Hash::extract($services, '{n}.Service.uuid');
        $hoststatus = $HoststatusTable->byUuid($hostsUuids, $HoststatusFields);
        $servicestatus = $ServicestatusTable->byUuid($servicesUuids, $ServicestatusFields);

        if (empty($hoststatus) && empty($servicestatus)) {
            return [
                'BitMaskHostState'    => $bitMaskHostState,
                'BitMaskServiceState' => $bitMaskServiceState,
                'Map'                 => $map
            ];
        }
        foreach ($hoststatus as $statusDetails) {
            $bitMaskHostState |= 1 << $statusDetails['Hoststatus']['current_state'];
        }
        foreach ($servicestatus as $statusDetails) {
            $bitMaskServiceState |= 1 << $statusDetails['Servicestatus']['current_state'];
        }
        return [
            'BitMaskHostState'    => $bitMaskHostState,
            'BitMaskServiceState' => $bitMaskServiceState,
            'Map'                 => $map
        ];
    }

    /**
     * @param HostsTable $HostsTable
     * @param ServicesTable $ServicesTable
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param array $hostgroup
     * @return array
     */
    public function getHostgroupSummary(HostsTable $HostsTable, ServicesTable $ServicesTable, HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $ServicestatusTable, array $hostgroup) {
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->output()
            ->perfdata()
            ->currentCheckAttempt()
            ->maxCheckAttempts()
            ->lastCheck()
            ->nextCheck()
            ->lastStateChange()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();

        $hostUuids = Hash::extract($hostgroup['hosts'], '{n}.uuid');

        $hoststatusByUuids = $HoststatusTable->byUuid($hostUuids, $HoststatusFields);
        $hostStateSummary = $HostsTable->getHostStateSummary($hoststatusByUuids, false);

        $ServicestatusFieds = new ServicestatusFields(new DbBackend());
        $ServicestatusFieds
            ->currentState()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged()
            ->output();
        $ServicestatusConditions = new ServicestatusConditions(new DbBackend());


        if (empty($hoststatusByUuids)) {
            $hoststatusByUuids['Hoststatus'] = [];
        }
        $hoststatusResult = [];
        $cumulatedHostState = -1;
        $cumulatedServiceState = null;
        $allServiceStatus = [];
        $totalServiceStateSummary = [
            'state' => [
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 0,
            ],
            'total' => 0
        ];

        $hostIdsGroupByState = [
            0 => [],
            1 => [],
            2 => []
        ];

        $serviceIdsGroupByState = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];


        foreach ($hostgroup['hosts'] as $host) {
            $Host = new \itnovum\openITCOCKPIT\Core\Views\Host(['Host' => $host]);
            if (isset($hoststatusByUuids[$Host->getUuid()])) {
                $Hoststatus = new Hoststatus(
                    $hoststatusByUuids[$Host->getUuid()]['Hoststatus']
                );
                $hostIdsGroupByState[$Hoststatus->currentState()][] = $host['id'];

                if ($Hoststatus->currentState() > $cumulatedHostState) {
                    $cumulatedHostState = $Hoststatus->currentState();
                }
            } else {
                $Hoststatus = new Hoststatus(
                    ['Hoststatus' => []]
                );
            }
            $services = $ServicesTable->find()
                ->join([
                    [
                        'table'      => 'servicetemplates',
                        'type'       => 'INNER',
                        'alias'      => 'Servicetemplates',
                        'conditions' => 'Servicetemplates.id = Services.servicetemplate_id',
                    ],
                ])
                ->select([
                    'Services.id',
                    'Services.name',
                    'Services.uuid',
                    'Servicetemplates.name'
                ])
                ->where([
                    'Services.host_id'  => $Host->getId(),
                    'Services.disabled' => 0
                ])->all()->toArray();

            $servicesUuids = Hash::extract($services, '{n}.uuid');
            $servicesIdsByUuid = Hash::combine($services, '{n}.uuid', '{n}.id');
            $servicestatusResults = $ServicestatusTable->byUuid($servicesUuids, $ServicestatusFieds, $ServicestatusConditions);

            $serviceIdsGroupByStatePerHost = [
                0 => [],
                1 => [],
                2 => [],
                3 => []
            ];
            foreach ($servicestatusResults as $serviceUuid => $servicestatusResult) {
                $allServiceStatus[] = $servicestatusResult['Servicestatus']['current_state'];
                $serviceIdsGroupByState[$servicestatusResult['Servicestatus']['current_state']][] = $servicesIdsByUuid[$serviceUuid];
                $serviceIdsGroupByStatePerHost[$servicestatusResult['Servicestatus']['current_state']][] = $servicesIdsByUuid[$serviceUuid];
            }

            $ServicestatusObjects = Servicestatus::fromServicestatusByUuid($servicestatusResults);
            $serviceStateSummary = ServiceStateSummary::getServiceStateSummary($ServicestatusObjects, false);

            $hoststatusResult[] = [
                'Host'                   => $Host->toArray(),
                'Hoststatus'             => $Hoststatus->toArray(),
                'ServiceSummary'         => $serviceStateSummary,
                'ServiceIdsGroupByState' => $serviceIdsGroupByStatePerHost
            ];

            foreach ($serviceStateSummary['state'] as $state => $stateValue) {
                $totalServiceStateSummary['state'][$state] += $stateValue;
            }
            $totalServiceStateSummary['total'] += $serviceStateSummary['total'];
        }
        $hoststatusResult = Hash::sort($hoststatusResult, '{s}.Hoststatus.currentState', 'desc');

        $hostgroup = [
            'id'                  => $hostgroup['id'],
            'name'                => $hostgroup['Containers']['name'],
            'description'         => $hostgroup['description'],
            'HostSummary'         => $hostStateSummary,
            'TotalServiceSummary' => $totalServiceStateSummary
        ];

        if ($cumulatedHostState > 0) {
            $CumulatedHostStatus = new Hoststatus([
                'current_state' => $cumulatedHostState
            ]);
            $CumulatedHumanState = $CumulatedHostStatus->toArray()['humanState'];
        } else {
            if (!empty($allServiceStatus)) {
                $cumulatedServiceState = (int)max($allServiceStatus);
            }
            $CumulatedServiceStatus = new Servicestatus([
                'current_state' => $cumulatedServiceState
            ]);
            $CumulatedHumanState = $CumulatedServiceStatus->toArray()['humanState'];
        }
        return [
            'Hostgroup'              => $hostgroup,
            'Hosts'                  => $hoststatusResult,
            'CumulatedHumanState'    => $CumulatedHumanState,
            'HostIdsGroupByState'    => $hostIdsGroupByState,
            'ServiceIdsGroupByState' => $serviceIdsGroupByState
        ];
    }

    /**
     * @param ServicesTable $ServicesTable
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $Servicestatus
     * @param array $host
     * @param UserTime $UserTime
     * @return array
     */
    public function getHostSummary(ServicesTable $ServicesTable, HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $Servicestatus, array $host, UserTime $UserTime) {
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->output()
            ->perfdata()
            ->currentCheckAttempt()
            ->maxCheckAttempts()
            ->lastCheck()
            ->nextCheck()
            ->lastStateChange()
            ->lastHardStateChange()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();

        $hoststatus = $HoststatusTable->byUuid($host['uuid'], $HoststatusFields);
        if (empty($hoststatus)) {
            $hoststatus['Hoststatus'] = [];
        }

        $hoststatus = new Hoststatus($hoststatus['Hoststatus'], $UserTime);

        $services = $ServicesTable->find()
            ->join([
                [
                    'table'      => 'servicetemplates',
                    'type'       => 'INNER',
                    'alias'      => 'Servicetemplates',
                    'conditions' => 'Servicetemplates.id = Services.servicetemplate_id',
                ],
            ])
            ->select([
                'Services.id',
                'Services.name',
                'Services.uuid',
                'Servicetemplates.name'
            ])
            ->where([
                'Services.host_id'  => $host['id'],
                'Services.disabled' => 0
            ])->all()->toArray();

        $ServicestatusFieds = new ServicestatusFields(new DbBackend());
        $ServicestatusFieds
            ->currentState()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged()
            ->output();
        $ServicestatusConditions = new ServicestatusConditions(new DbBackend());

        $servicesUuids = Hash::extract($services, '{n}.uuid');
        $servicestatusResults = $Servicestatus->byUuid($servicesUuids, $ServicestatusFieds, $ServicestatusConditions);

        $ServicestatusObjects = Servicestatus::fromServicestatusByUuid($servicestatusResults);
        $ServiceSummary = ServiceStateSummary::getServiceStateSummary($ServicestatusObjects, false);
        $serviceIdsGroupByState = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];
        $servicesResult = [];
        foreach ($services as $service) {
            $Service = new \itnovum\openITCOCKPIT\Core\Views\Service($service);
            if (isset($servicestatusResults[$Service->getUuid()])) {
                $Servicestatus = new Servicestatus(
                    $servicestatusResults[$Service->getUuid()]['Servicestatus']
                );
                $serviceIdsGroupByState[$Servicestatus->currentState()][] = $service['id'];
            } else {
                $Servicestatus = new Servicestatus(
                    ['Servicestatus' => []]
                );
            }

            $servicesResult[] = [
                'Service'       => $Service->toArray(),
                'Servicestatus' => $Servicestatus->toArray()
            ];
        }
        $servicesResult = Hash::sort($servicesResult, '{s}.Servicestatus.currentState', 'desc');

        $Host = new \itnovum\openITCOCKPIT\Core\Views\Host($host);
        return [
            'Host'                   => $Host->toArray(),
            'Hoststatus'             => $hoststatus->toArray(),
            'Services'               => $servicesResult,
            'ServiceSummary'         => $ServiceSummary,
            'ServiceIdsGroupByState' => $serviceIdsGroupByState
        ];
    }

    /**
     * @param ServicesTable $ServicesTable
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicestatusTableInterface $Servicestatus
     * @param array $service
     * @param UserTime $UserTime
     * @return array
     */
    public function getServiceSummary(ServicesTable $ServicesTable, HoststatusTableInterface $HoststatusTable, ServicestatusTableInterface $Servicestatus, array $service, UserTime $UserTime) {
        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();

        $hoststatus = $HoststatusTable->byUuid($service['host']['uuid'], $HoststatusFields);
        if (empty($hoststatus)) {
            $hoststatus['Hoststatus'] = [];
        }

        $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus(
            $hoststatus['Hoststatus'],
            $UserTime
        );

        $ServicestatusFieds = new ServicestatusFields(new DbBackend());
        $ServicestatusFieds
            ->currentState()
            ->isHardstate()
            ->output()
            ->perfdata()
            ->currentCheckAttempt()
            ->maxCheckAttempts()
            ->lastCheck()
            ->nextCheck()
            ->lastStateChange()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();

        $ServicestatusConditions = new ServicestatusConditions(new DbBackend());

        $Servicestatus = $Servicestatus->byUuid($service['uuid'], $ServicestatusFieds, $ServicestatusConditions);
        $Service = new \itnovum\openITCOCKPIT\Core\Views\Service($service);
        if (!empty($Servicestatus)) {
            $Servicestatus = new Servicestatus(
                $Servicestatus['Servicestatus'],
                $UserTime
            );
        } else {
            $Servicestatus = new Servicestatus(
                ['Servicestatus' => []]
            );
        }
        $Host = new \itnovum\openITCOCKPIT\Core\Views\Host($service['host']);

        return [
            'Host'          => $Host->toArray(),
            'Hoststatus'    => $hoststatus->toArray(),
            'Service'       => $Service->toArray(),
            'Servicestatus' => $Servicestatus->toArray()
        ];
    }

    /**
     * @param ServicesTable $ServicesTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param array $servicegroup
     * @return array
     */
    public function getServicegroupSummary(ServicesTable $ServicesTable, ServicestatusTableInterface $ServicestatusTable, array $servicegroup) {
        $ServicestatusFields = new ServicestatusFields(new DbBackend());
        $ServicestatusFields
            ->currentState()
            ->isHardstate()
            ->output()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();

        $serviceUuids = Hash::extract($servicegroup['services'], '{n}.uuid');
        $ServicestatusConditions = new ServicestatusConditions(new DbBackend());

        $servicestatusResults = $ServicestatusTable->byUuid($serviceUuids, $ServicestatusFields, $ServicestatusConditions);
        $ServicestatusObjects = Servicestatus::fromServicestatusByUuid($servicestatusResults);
        $serviceStateSummary = ServiceStateSummary::getServiceStateSummary($ServicestatusObjects, false);
        $serviceIdsGroupByState = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];
        $cumulatedServiceState = null;
        $servicesResult = [];
        foreach ($servicegroup['services'] as $service) {
            $Service = new \itnovum\openITCOCKPIT\Core\Views\Service([
                'Service'         => $service,
                'Servicetemplate' => $service['servicetemplate']
            ]);
            $Host = new \itnovum\openITCOCKPIT\Core\Views\Host($service['host']);

            if (isset($servicestatusResults[$Service->getUuid()])) {
                $Servicestatus = new Servicestatus(
                    $servicestatusResults[$Service->getUuid()]['Servicestatus']
                );
                $serviceIdsGroupByState[$Servicestatus->currentState()][] = $service['id'];

            } else {
                $Servicestatus = new Servicestatus(
                    ['Servicestatus' => []]
                );
            }
            $servicesResult[] = [
                'Service'       => $Service->toArray(),
                'Servicestatus' => $Servicestatus->toArray(),
                'Host'          => $Host->toArray()
            ];
        }
        $servicesResult = Hash::sort($servicesResult, '{s}.Servicestatus.currentState', 'desc');
        if (!empty($servicestatusResults)) {
            $cumulatedServiceState = Hash::apply($servicestatusResults, '{s}.Servicestatus.current_state', 'max');
        }
        $CumulatedServiceStatus = new Servicestatus([
            'current_state' => $cumulatedServiceState
        ]);

        $servicegroup = [
            'id'          => $servicegroup['id'],
            'name'        => $servicegroup['container']['name'],
            'description' => $servicegroup['description']
        ];

        return [
            'Servicegroup'           => $servicegroup,
            'ServiceSummary'         => $serviceStateSummary,
            'Services'               => $servicesResult,
            'CumulatedHumanState'    => $CumulatedServiceStatus->toArray()['humanState'],
            'ServiceIdsGroupByState' => $serviceIdsGroupByState
        ];
    }

    /**
     * @param HostsTable $HostsTable
     * @param HoststatusTableInterface $HoststatusTable
     * @param ServicesTable $ServicesTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param array $map
     * @param array $hosts
     * @param array $services
     * @param UserTime $UserTime
     * @param bool $summaryStateItem
     * @return array
     */
    public function getMapSummary(HostsTable $HostsTable, HoststatusTableInterface $HoststatusTable, ServicesTable $ServicesTable, ServicestatusTableInterface $ServicestatusTable, array $map, array $hosts, array $services, UserTime $UserTime, bool $summaryStateItem) {
        $cumulatedHostState = null;
        $cumulatedServiceState = null;
        $notOkHosts = [];
        $notOkServices = [];
        $hostIdsGroupByState = [
            0 => [],
            1 => [],
            2 => []
        ];

        $serviceIdsGroupByState = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];
        $counterForNotOkHostAndService = 0;
        $limitForNotOkHostAndService = 20;

        $HoststatusFields = new HoststatusFields(new DbBackend());
        $HoststatusFields
            ->currentState()
            ->lastCheck()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged()
            ->output();

        $hostUuids = Hash::extract($hosts, '{n}.uuid');

        $hoststatusByUuids = $HoststatusTable->byUuid($hostUuids, $HoststatusFields);
        $hostStateSummary = $HostsTable->getHostStateSummary($hoststatusByUuids, false);

        $ServicestatusFieds = new ServicestatusFields(new DbBackend());
        $ServicestatusFieds
            ->currentState()
            ->lastCheck()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged()
            ->output();
        $ServicestatusConditions = new ServicestatusConditions(new DbBackend());

        $servicesUuids = Hash::extract($services, '{n}.Service.uuid');

        $servicestatusResults = $ServicestatusTable->byUuid($servicesUuids, $ServicestatusFieds, $ServicestatusConditions);
        $ServicestatusObjects = Servicestatus::fromServicestatusByUuid($servicestatusResults);
        $serviceStateSummary = ServiceStateSummary::getServiceStateSummary($ServicestatusObjects, false);

        if (!empty($hoststatusByUuids)) {
            $worstHostState = array_values(
                $hoststatusByUuids = Hash::sort($hoststatusByUuids, '{s}.Hoststatus.current_state', 'desc')
            );
            $cumulatedHostState = (int)$worstHostState[0]['Hoststatus']['current_state'];
            $hosts = Hash::combine($hosts, '{n}.uuid', '{n}');
            foreach ($hoststatusByUuids as $hostUuid => $hoststatusByUuid) {
                $hostStatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($hoststatusByUuid['Hoststatus'], $UserTime);
                $currentHostState = $hostStatus->currentState();

                $hostIdsGroupByState[$currentHostState][] = $hosts[$hostUuid]['id'];
                $host = new \itnovum\openITCOCKPIT\Core\Views\Host($hosts[$hostUuid]);
                if ($counterForNotOkHostAndService <= $limitForNotOkHostAndService && $currentHostState > 0) {
                    $notOkHosts[] = [
                        'Hoststatus' => $hostStatus->toArray(),
                        'Host'       => $host->toArray()
                    ];
                    $counterForNotOkHostAndService++;
                }
            }
        }

        if (!empty($servicestatusResults)) {
            $worstServiceState = array_values(
                $servicestatusResults = Hash::sort($servicestatusResults, '{s}.Servicestatus.current_state', 'desc')
            );
            $cumulatedServiceState = (int)$worstServiceState[0]['Servicestatus']['current_state'];
            $services = Hash::combine($services, '{n}.Service.uuid', '{n}');
            foreach ($servicestatusResults as $serviceUuid => $servicestatusByUuid) {
                $serviceStatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($servicestatusByUuid['Servicestatus'], $UserTime);
                $currentServiceState = $serviceStatus->currentState();
                $serviceIdsGroupByState[$currentServiceState][] = $services[$serviceUuid]['Service']['id'];

                $service = new \itnovum\openITCOCKPIT\Core\Views\Service($services[$serviceUuid]);
                if ($counterForNotOkHostAndService <= $limitForNotOkHostAndService && $currentServiceState > 0) {
                    $notOkServices[] = [
                        'Servicestatus' => $serviceStatus->toArray(),
                        'Service'       => $service->toArray()
                    ];
                    $counterForNotOkHostAndService++;
                }
            }
        }

        $CumulatedHostStatus = new \itnovum\openITCOCKPIT\Core\Hoststatus([
            'current_state' => $cumulatedHostState
        ]);

        $CumulatedHumanState = $CumulatedHostStatus->toArray()['humanState'];
        if (($cumulatedHostState === 0 || is_null($cumulatedHostState)) && !is_null($cumulatedServiceState)) {
            $CumulatedServiceStatus = new \itnovum\openITCOCKPIT\Core\Servicestatus([
                'current_state' => $cumulatedServiceState
            ]);
            $CumulatedHumanState = $CumulatedServiceStatus->toArray()['humanState'];
        }

        $map = [
            'id'        => $map['id'],
            'name'      => $map['name'],
            'title'     => $map['title'],
            'object_id' => ($summaryStateItem) ? $map['Mapsummaryitems']['object_id'] : $map['Mapitems']['object_id']
        ];

        return [
            'Map'                    => $map,
            'HostSummary'            => $hostStateSummary,
            'ServiceSummary'         => $serviceStateSummary,
            'CumulatedHumanState'    => $CumulatedHumanState,
            'NotOkHosts'             => $notOkHosts,
            'NotOkServices'          => $notOkServices,
            'HostIdsGroupByState'    => $hostIdsGroupByState,
            'ServiceIdsGroupByState' => $serviceIdsGroupByState
        ];
    }

    /**
     * @param $id
     * @return array
     */
    public function getMapForMapwidget($id) {
        $query = $this->find()
            ->contain([
                'Mapitems',
                'Maplines',
                'Mapgadgets',
                'Mapicons',
                'Maptexts',
                'Mapsummaryitems',
                'Containers'
            ])
            ->where([
                'Maps.id' => $id
            ]);

        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $query->toArray();
    }
}
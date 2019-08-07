<?php

namespace Statusengine2Module\Model\Table;

use App\Lib\Interfaces\StatehistoryHostTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\StatehistoryHostConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use Statusengine2Module\Model\Entity\StatehistoryHost;

/**
 * StatehistoryHost Model
 *
 * @property \Statusengine2Module\Model\Table\StatehistoryHostsTable|\Cake\ORM\Association\BelongsTo $Statehistories
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Objects
 *
 * @method \Statusengine2Module\Model\Entity\StatehistoryHost get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryHost newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryHost[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryHost|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryHost saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryHost[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryHost findOrCreate($search, callable $callback = null, $options = [])
 */
class StatehistoryHostsTable extends Table implements StatehistoryHostTableInterface {

    /*****************************************************/
    /*                         !!!                       */
    /*           If you add a method to this table       */
    /*   define it in the implemented interface first!   */
    /*                         !!!                       */
    /*****************************************************/

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) {
        parent::initialize($config);

        $this->setTable('nagios_statehistory');
        $this->setDisplayField('statehistory_id');
        $this->setPrimaryKey(['statehistory_id', 'state_time']);

        $this->belongsTo('Objects', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'className'  => 'Statusengine2Module.Objects',
            'conditions' => [
                'Objects.objecttype_id' => 1
            ]
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) {
        //Readonly table
        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) {
        //Readonly table
        return $rules;
    }

    /**
     * @param StatehistoryHostConditions $StatehistoryHostConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getStatehistoryIndex(StatehistoryHostConditions $StatehistoryHostConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->contain([
                'Objects'
            ])
            ->where([
                'Objects.name1'                  => $StatehistoryHostConditions->getHostUuid(),
                'StatehistoryHosts.state_time >' => date('Y-m-d H:i:s', $StatehistoryHostConditions->getFrom()),
                'StatehistoryHosts.state_time <' => date('Y-m-d H:i:s', $StatehistoryHostConditions->getTo())
            ])
            ->order($StatehistoryHostConditions->getOrder());

        if ($StatehistoryHostConditions->hasConditions()) {
            $query->andWhere($StatehistoryHostConditions->getConditions());
        }

        if (!empty($StatehistoryHostConditions->getStates())) {
            $query->andWhere([
                'StatehistoryHosts.state IN' => $StatehistoryHostConditions->getStates()
            ]);
        }


        if (!empty($StatehistoryHostConditions->getStateTypes())) {
            $query->andWhere([
                'StatehistoryHosts.state_type IN' => $StatehistoryHostConditions->getStateTypes()
            ]);
        }

        if ($StatehistoryHostConditions->hardStateTypeAndUpState()) {
            $query->andWhere([
                'OR' => [
                    'StatehistoryHosts.state_type' => 1,
                    'StatehistoryHosts.state'      => 0
                ]
            ]);
        }


        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->emptyArrayIfNull($query->toArray());
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
     * @param StatehistoryHostConditions $StatehistoryHostConditions
     * @param bool $enableHydration
     * @return array|StatehistoryHost|null
     */
    public function getLastRecord(StatehistoryHostConditions $StatehistoryHostConditions, $enableHydration = true) {
        $query = $this->find()
            ->contain([
                'Objects'
            ])
            ->where([
                'Objects.name1'                   => $StatehistoryHostConditions->getHostUuid(),
                'StatehistoryHosts.state_time <=' => date('Y-m-d H:i:s', $StatehistoryHostConditions->getFrom())
            ])
            ->order([
                'StatehistoryHosts.state_time' => 'DESC'
            ])
            ->enableHydration($enableHydration)
            ->first();

        return $query;
    }
}
<?php
namespace App\Model\Behavior;

use Exception;

use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Session;


class TrackDeleteBehavior extends Behavior {
/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
	public function implementedEvents() 
    {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.beforeDelete' => 'beforeDelete'
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

    public function beforeDelete(Event $event, Entity $entity) 
    {
        $this->trackDelete($entity);
    }

    public function trackDelete(Entity $entity) 
    {
        try {
            $DeletedRecords = TableRegistry::get('DeletedRecords');
            $entityTable = TableRegistry::get($entity->source());
            $session = new Session();
            $userId = $session->read('Auth.User.id');

            if (!is_array($entityTable->primaryKey())) { // single primary key
                $referenceKey = $entity->{$entityTable->primaryKey()};
            } else { // composite primary keys
                if ($entity->has('id')) {
                    $referenceKey = $entity->id;
                } else {
                    $referenceKey = '';
                }
            }

            // catering for 'binary' field type start
            $binaryDataFieldNames = [];
            $schema = $entityTable->schema();
            foreach ($schema->columns() as $key => $value) {
                $schemaColumnData = $schema->column($value);
                if (array_key_exists('type', $schemaColumnData) && $schemaColumnData['type'] == 'binary') {
                    $binaryDataFieldNames[] = $value;
                }
            }
            $entityData = $entity->toArray();
            foreach ($binaryDataFieldNames as $key => $value) {
                if (array_key_exists($value, $entityData)) {
                    if (is_null($entityData[$value])) continue;
                    $file = base64_encode($this->convertBinaryResourceToString($entityData[$value]));
                    $entityData[$value] = $file;
                }
            }
            // catering for 'binary' field type end
            
            $newEntity = $DeletedRecords->newEntity([
                'reference_table' => $entity->source(),
                'reference_key' => $referenceKey,
                'data' => json_encode($entityData),
                'created_user_id' => $userId,
                'created' => new Time('NOW')
            ]);
            $DeletedRecords->save($newEntity);
        } catch (Exception $e) {
            Log::write('error', __METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function convertBinaryResourceToString($phpResourceFile) {
        $file = ''; 
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192); 
        } 
        fclose($phpResourceFile);

        return $file;
    }
	
}
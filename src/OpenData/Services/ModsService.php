<?php

namespace OpenData\Services;

class ModsService extends BaseService {

    public function findIn($signatures = array()) {
        return $this->db->mods->find(
                        array('_id' => array('$in' => $signatures))
        );
    }

    public function create($id) {
        try {
            $this->db->mods->insert(array(
                '_id' => $id
            ));
        } catch (\MongoCursorException $e) {
            return false;
        }
        return true;
    }

    public function findByModId($modId) {
        return $this->db->mods->find(
                        array('mods.modId' => $modId)
        );
    }

    public function append($file) {

        $signature = $file['signature'];

        $currentEntry = $this->findOne($signature);

        if ($currentEntry == null) {
            return false;
        }
        foreach ($file as $k => $v) {
            if (!isset($currentEntry[$k])) {
                $currentEntry[$k] = $v;
            }
        }

        unset($file['signature']);
        unset($currentEntry['_id']);

        $this->db->mods->update(
                array('_id' => $signature), array('$set' => $currentEntry)
        );

        return true;
    }

    public function findOne($signature) {
        return $this->db->mods->findOne(array('_id' => $signature));
    }

    public function findUniqueMods() {

        $results = $this->db->mods->aggregate(
           array('$project' => array('mods' => 1 )),
           array('$unwind' => '$mods'),
           array('$match' => array('mods.parent' => '')),
           array('$group' => array(
                '_id' => '$mods.modId',
                'data' => array('$first' => '$mods')))
        );

        $mods = array();
        foreach ($results['result'] as $result) {
            if (isset($result['data'])) {
                $mods[] = $result['data'];
            }
        }

        usort($mods, function($a, $b) {
            $al = strtolower($a['modId']);
            $bl = strtolower($b['modId']);
            if ($al == $bl) {
                return 0;
            }
            return ($al > $bl) ? +1 : -1;
        });

        return $mods;
    }

}

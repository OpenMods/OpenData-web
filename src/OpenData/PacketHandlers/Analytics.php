<?php


namespace OpenData\PacketHandlers;

class Analytics extends SignaturesBase {

    public function __construct($files) {
        parent::__construct($files);
    }

    private function stat_increment($redis, $today, $category, $key, $amount = 1) {
        $redis->hincrby("counters:$today:$category", $key, $amount);
        $redis->hincrby("counters:total:$category", $key, $amount);
    }

    public function execute($packet) {
        $redis = new \Predis\Client();

        $now = time();
        $today = @date("Y-m-d", $now);

        $this->stat_increment($redis, $today, "java", $packet['javaVersion']);
        $this->stat_increment($redis, $today, "side", $packet['side']);
        $this->stat_increment($redis, $today, "minecraft", $packet['minecraft']);
        $this->stat_increment($redis, $today, "language", $packet['language']);
        $this->stat_increment($redis, $today, "locale", $packet['locale']);
        $this->stat_increment($redis, $today, "timezone", $packet['timezone']);
        $this->stat_increment($redis, $today, "obfuscated", $packet['obfuscated']);
        foreach ($packet['runtime'] as $k => $v) {
            $this->stat_increment($redis, $today, "runtime-$k", $v);
        }

        if (isset($packet["tags"])) {
            foreach ($packet["tags"] as $tag) {
                $this->stat_increment($redis, $today, "tags", $tag);
            }
        }

        foreach ($packet['signatures'] as $signature) {
            $signatureHash = $signature['signature'];
            $signatureFilename = $signature['filename'];
            if (!$this->isIgnoredFilename($signatureFilename)) {
                $signatureKey = "file_stats:" . $signatureHash;
                $redis->hincrby($signatureKey, "seenMc:{$packet['minecraft']}", 1);
            }
        }

        $now = time();

        if (isset($packet["installedSignatures"])) {
            foreach ($packet["installedSignatures"] as $addedSig) {
                $sigKey = "file_stats:".$addedSig;
                $redis->hsetnx($sigKey, "firstInstall", $now);
                $redis->hset($sigKey, "lastInstall", $now);
                $redis->hincrby($sigKey, "timesInstall", 1);
            }
        }

        if (isset($packet["uninstalledSignatures"])) {
            foreach ($packet["uninstalledSignatures"] as $removedSig) {
                $sigKey = "file_stats:".$removedSig;
                $redis->hsetnx($sigKey, "firstUninstall", $now);
                $redis->hset($sigKey, "lastUninstall", $now);
                $redis->hincrby($sigKey, "timesUninstall", 1);
            }
        }

        return parent::execute($packet);
    }

    public function getPacketType() {
        return 'analytics';
    }

    public function getJsonSchema() {
        return 'analytics.json';
    }
}

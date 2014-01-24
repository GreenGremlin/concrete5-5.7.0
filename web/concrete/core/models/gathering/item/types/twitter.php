<?php
defined('C5_EXECUTE') or die("Access Denied.");
class Concrete5_Model_TwitterGatheringItem extends GatheringItem {

	public function loadDetails() {}
	public function canViewGatheringItem() {return true;}

	public static function getListByItem($mixed) {
		$ags = GatheringDataSource::getByHandle('twitter');
		return GatheringItem::getListByKey($ags, $mixed->get_link());
	}

	public static function add(GatheringDataSourceConfiguration $configuration, $tweet) {
		$gathering = $configuration->getGatheringObject();
		try {
			// we wrap this in a try because it MIGHT fail if it's a duplicate
			$item = parent::add($gathering, $configuration->getGatheringDataSourceObject(), date('Y-m-d H:i:s', strtotime($tweet->created_at)), $tweet->text, $tweet->id);
		} catch(Exception $e) {}

		if (is_object($item)) {
			$item->assignFeatureAssignments($tweet);
			$item->setAutomaticGatheringItemTemplate();
			return $item;
		}
	}

	public function assignFeatureAssignments($tweet) {
		$userMentions = $tweet->entities->user_mentions;
		if(count($userMentions) > 0) {  // link mentions
			foreach($tweet->entities->user_mentions as $mention) {
				$tweet->text = str_replace('@'.$mention->screen_name, '<a target="_blank" href="http://www.twitter.com/'.$mention->screen_name.'">@'.$mention->screen_name.'</a>', $tweet->text);
			}
		}
		if(count($tweet->entities->hashtags) > 0) {   //link hashtags
			foreach($tweet->entities->hashtags as $hash) {
				$tweet->text = str_replace('#'.$hash->text, '<a target="_blank" href="http://www.twitter.com/search/%23'.$hash->text.'">#'.$hash->text.'</a>', $tweet->text);
			}
		}
		if(count($tweet->entities->urls) > 0) {
			foreach($tweet->entities->urls as $url) {
				$tweet->text = str_replace($url->url, '<a target="_blank" href="'.$url->url.'">'.$url->url.'</a>', $tweet->text);
			}
		}
		if(count($tweet->entities->media) > 0) {
			foreach($tweet->entities->media as $medium) {
				$tweet->text = str_replace($medium->url, '<a target="_blank" href="'.$medium->url.'">'.$medium->display_url.'</a>', $tweet->text);
			}
		}
		$this->addFeatureAssignment('tweet', $tweet->text);
		$this->addFeatureAssignment('date_time', $tweet->created_at);
		$this->addFeatureAssignment('description', $tweet->user->name);
	}

}

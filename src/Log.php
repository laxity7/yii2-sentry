<?php

namespace sheershoff\sentry;

use Yii;

class Log{

	/*
	 * Sends exception to Sentry using raven component
	 */
	public static function sendException($message,$category,$exception){
		if($exception!==null&&Yii::$app->has('raven')) {
			Yii::$app->get('raven')->getClient()->extra_context($message);
			Yii::$app->get('raven')->getClient()->captureException($exception);
		}
	}

	/*
	 * Proxy for Yii::error method, that also sends exception data to Sentry
	 */
	public static function error($message,$category = 'application',$exception=null){
		Yii::error($message,$category);
		self::sendException($message,$category,$exception);
	}

	/*
    * Proxy for Yii::warning method, that also sends exception data to Sentry
    */
	public static function warning($message,$category = 'application',$exception=null){
		Yii::warning($message,$category);
		self::sendException($message,$category,$exception);
	}

	/*
    * Proxy for Yii::info method, that also sends exception data to Sentry
    */
	public static function info($message,$category = 'application',$exception=null){
		Yii::info($message,$category);
		self::sendException($message,$category,$exception);
	}

	/*
    * Proxy for Yii::trace method, that also sends exception data to Sentry
    */
	public static function trace($message,$category = 'application',$exception=null){
		Yii::trace($message,$category);
		self::sendException($message,$category,$exception);
	}

}
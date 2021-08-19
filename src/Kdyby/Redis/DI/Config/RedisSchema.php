<?php declare(strict_types = 1);

namespace Kdyby\Redis\DI\Config;

class RedisSchema implements \Nette\Schema\Schema
{

	private \Nette\DI\ContainerBuilder $builder;

	private ?\Nette\Schema\Schema $schema = NULL;


	public function __construct(\Nette\DI\ContainerBuilder $builder)
	{
		$this->builder = $builder;
	}

	public function normalize($value, \Nette\Schema\Context $context)
	{
		$keys = [
			'host',
			'port',
			'timeout',
			'database',
			'auth',
			'persistent',
			'connectionAttempts',
			'lockDuration',
			'lockAcquireTimeout',
			'debugger',
			'versionCheck',
		];

		$client = [];

		foreach ($keys as $key) {
			if (\array_key_exists($key, $value)) {
				$client[$key] = $value[$key];
				unset($value[$key]);
			}
		}

		$value['clients'][NULL] = $client;

		return $this->getSchema()->normalize($value, $context);
	}


	public function merge($value, $base)
	{
		return \Nette\Schema\Helpers::merge($value, $base);
	}


	public function complete($value, \Nette\Schema\Context $context)
	{
		$value = $this->expandParameters((array) $value);

		$value = $this->normalize($value, $context);
		$value = $this->getSchema()->complete($value, $context);

		return $value;
	}


	public function completeDefault(\Nette\Schema\Context $context)
	{

	}

	private function expandParameters(array $config): array
	{
		$params = $this->builder->parameters;
		if (isset($config['parameters'])) {
			foreach ((array) $config['parameters'] as $k => $v) {
				$v = \explode(' ', \is_int($k) ? $v : $k);
				$params[\end($v)] = $this->builder::literal('$' . \end($v));
			}
		}
		return \Nette\DI\Helpers::expand($config, $params);
	}

	private function getSchema(): \Nette\Schema\Schema
	{
		if ($this->schema === NULL) {
			$this->schema = \Nette\Schema\Expect::structure([
				'journal' => \Nette\Schema\Expect::bool(FALSE),
				'storage' => \Nette\Schema\Expect::bool(FALSE),
				'session' => \Nette\Schema\Expect::bool(FALSE),
				'clients' => \Nette\Schema\Expect::arrayOf(
					new \Kdyby\Redis\DI\Config\ClientSchema($this->builder)
				)->default([]),
			]);
		}

		return $this->schema;
	}

}

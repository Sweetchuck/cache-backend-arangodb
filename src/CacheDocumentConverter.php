<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use ArangoDBClient\Document;
use Cache\Adapter\Common\HasExpirationTimestampInterface;
use Cache\Adapter\Common\PhpCacheItem;
use Psr\Cache\CacheItemInterface;

class CacheDocumentConverter implements CacheDocumentConverterInterface
{

    //region documentClass
    protected string $documentClass = CacheDocument::class;

    public function getDocumentClass(): string
    {
        return $this->documentClass ?: Document::class;
    }

    public function setDocumentClass(string $documentClass): static
    {
        $this->documentClass = $documentClass;

        return $this;
    }
    //endregion

    /**
     * {@inheritdoc}
     */
    public function documentToItem(
        CacheItemPool $pool,
        Document $document
    ): CacheItem {
        $item = new CacheItem(
            $document->get('key'),
            $document->get('tags'),
            $pool->getValidator(),
        );
        $item->set($pool->getSerializer()->unserialize($document->get('value')));

        $expires = $document->get('expires');
        if ($expires !== null) {
            $item->expiresAt(\DateTime::createFromFormat('U', (string) intval($expires)));
        }

        $item->setCreatedTimestamp($document->get('created'));

        return $item;
    }

    public function itemToUpsertUpdateBindVars(
        CacheItemPool $pool,
        CacheItem $item
    ): array {
        $key = $item->getKey();

        $bindVars = [
            'condition' => ['key' => $key],
            'insert' => [
                'key' => $key,
                'value' => $pool->getSerializer()->serialize($item->get()),
                'created' => (float) $item->getNow()->format('U.u'),
            ],
        ];

        if ($item instanceof HasExpirationTimestampInterface
            && ($expires = $item->getExpirationTimestamp())
        ) {
            $bindVars['insert']['expires'] = $expires;
        }

        if ($item instanceof PhpCacheItem
            && ($tags = $item->getTags())
        ) {
            $pool->getValidator()->assertTags($tags);
            $bindVars['insert']['tags'] = array_unique($tags);
            sort($bindVars['insert']['tags']);
        }

        $bindVars['update'] = $bindVars['insert'];
        unset($bindVars['update']['key']);

        return $bindVars;
    }

    public function documentFromItem(CacheItemPool $pool, CacheItemInterface $item): Document
    {
        $documentClass = $this->getDocumentClass();
        /** @var \Sweetchuck\CacheBackend\ArangoDb\CacheDocument $document */
        $document = new $documentClass();

        $document->key = $item->getKey();
        $document->value = $pool->getSerializer()->serialize($item->get());

        if ($item instanceof HasExpirationTimestampInterface) {
            $document->expires = $item->getExpirationTimestamp();
        }

        if ($item instanceof PhpCacheItem) {
            $tags = array_values($item->getTags());
            sort($tags);

            $document->tags = $tags;
        }

        if ($item instanceof CacheItem) {
            $document->created = $item->getCreatedTimestamp();
        }

        return $document;
    }
}

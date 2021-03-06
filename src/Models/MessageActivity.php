<?php
/**
 * Yasmin
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved.
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
 */

namespace CharlotteDunois\Yasmin\Models;

use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Utils\DataHelpers;
use RuntimeException;

/**
 * Represents a message activity.
 *
 * @property string|null $partyID   The party ID associated with this message activity, or null.
 * @property int $type      The message activity type. ({@see self::TYPES})
 * @property User|null $user      The user this message activity is for.
 *
 * @property Activity|null $activity  The activity this message activity points to, or null.
 */
class MessageActivity extends ClientBase
{
    /**
     * The Message Activity types.
     *
     * @var array
     * @source
     */
    const TYPES = [
        'JOIN'         => 1,
        'SPECTATE'     => 2,
        'LISTEN'       => 3,
        'JOIN_REQUEST' => 5,
    ];

    /**
     * The party ID associated with this message activity, or null.
     *
     * @var string|null
     */
    protected $partyID;

    /**
     * The message activity type.
     *
     * @var int
     */
    protected $type;

    /**
     * The user this message activity is for.
     *
     * @var User|null
     */
    protected $user;

    /**
     * @param  Client  $client
     * @param  array  $activity
     *
     * @internal
     */
    public function __construct(Client $client, array $activity)
    {
        parent::__construct($client);

        $this->partyID = DataHelpers::typecastVariable(
            ($activity['party_id'] ?? null),
            'string'
        );
        $this->type = (int) $activity['type'];

        if ($activity['party_id'] !== null) {
            $name = explode(':', $activity['party_id']);
            $uid = (string) ($name[1] ?? $name[0]);
            $this->user = $this->client->users->get($uid);
        }
    }

    /**
     * {@inheritdoc}
     * @return mixed
     * @throws RuntimeException
     * @internal
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        switch ($name) {
            case 'activity':
                if ($this->user) {
                    $presence = $this->user->getPresence();
                    if ($presence !== null && $presence->activity !== null) {
                        return $presence->activity;
                    }
                }

                return null;
                break;
        }

        return parent::__get($name);
    }
}

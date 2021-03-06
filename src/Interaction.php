<?php

namespace Liliom\Acquaintances;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use stdClass;

/**
 * Class Interaction.
 */
class Interaction
{
    use SoftDeletes;
    const RELATION_LIKE = 'like';
    const RELATION_FOLLOW = 'follow';
    const RELATION_SUBSCRIBE = 'subscribe';
    const RELATION_FAVORITE = 'favorite';
    const RELATION_UPVOTE = 'upvote';
    const RELATION_DOWNVOTE = 'downvote';


    /**
     * @var array
     */
    protected static $relationMap = [
        'followings' => 'follow',
        'followers' => 'follow',
        'likes' => 'like',
        'likers' => 'like',
        'favoriters' => 'favorite',
        'favorites' => 'favorite',
        'subscriptions' => 'subscribe',
        'subscribers' => 'subscribe',
        'upvotes' => 'upvote',
        'upvoters' => 'upvote',
        'downvotes' => 'downvote',
        'downvoters' => 'downvote',
    ];

    /**
     * @param \Illuminate\Database\Eloquent\Model              $model
     * @param string                                           $relation
     * @param array|string|\Illuminate\Database\Eloquent\Model $target
     * @param string                                           $class
     *
     * @return bool
     */
    public static function isRelationExists(Model $model, $relation, $target, $class = null)
    {
        $target = self::formatTargets($target, $class ?: config('auth.providers.users.model'));

        return $model->{$relation}($target->classname)
                     ->where($class ? 'subject_id' : 'user_id',
                         head($target->ids))->exists();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model              $model
     * @param string                                           $relation
     * @param array|string|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                           $class
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function attachRelations(Model $model, $relation, $targets, $class)
    {
        $targets = self::attachPivotsFromRelation($model->{$relation}(), $targets, $class);

        return $model->{$relation}($targets->classname)->sync($targets->targets, false);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model              $model
     * @param string                                           $relation
     * @param array|string|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                           $class
     *
     * @return array
     */
    public static function detachRelations(Model $model, $relation, $targets, $class)
    {
        $targets = self::formatTargets($targets, $class);

        return $model->{$relation}($targets->classname)->detach($targets->ids);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model              $model
     * @param string                                           $relation
     * @param array|string|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                           $class
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function toggleRelations(Model $model, $relation, $targets, $class)
    {
        $targets = self::attachPivotsFromRelation($model->{$relation}(), $targets, $class);

        return $model->{$relation}($targets->classname)->toggle($targets->targets);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\MorphToMany $morph
     * @param array|string|\Illuminate\Database\Eloquent\Model    $targets
     * @param string                                              $class
     *
     * @throws \Exception
     *
     * @return \stdClass
     */
    public static function attachPivotsFromRelation(MorphToMany $morph, $targets, $class)
    {
        return self::formatTargets($targets, $class, [
            'relation' => self::getRelationTypeFromRelation($morph),
//            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * @param array|string|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                           $classname
     * @param array                                            $update
     *
     * @return \stdClass
     */
    public static function formatTargets($targets, $classname, array $update = [])
    {
        $result = new stdClass();
        $result->classname = $classname;

        if ( ! is_array($targets)) {
            $targets = [$targets];
        }

        $result->ids = array_map(function ($target) use ($result) {
            if ($target instanceof Model) {
                $result->classname = get_class($target);

                return $target->getKey();
            }

            return intval($target);
        }, $targets);

        $result->targets = empty($update) ? $result->ids : array_combine($result->ids,
            array_pad([], count($result->ids), $update));

        return $result;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\MorphToMany $relation
     *
     * @throws \Exception
     *
     * @return array
     */
    protected static function getRelationTypeFromRelation(MorphToMany $relation)
    {
        if ( ! \array_key_exists($relation->getRelationName(), self::$relationMap)) {
            throw new \Exception('Invalid relation definition.');
        }

        return self::$relationMap[$relation->getRelationName()];
    }
}

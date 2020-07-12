<?php

namespace App\Http\Controllers\Api;

use App\Http\Queries\TopicQuery;
use App\Http\Requests\Api\TopicRequest;
use App\Http\Resources\TopicResource;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TopicsController extends Controller
{
    /**
     * 话题列表
     * @param Request $request
     * @param TopicQuery $query
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request, TopicQuery $query)
    {
        $topics = $query->paginate();

//
//        $topics = QueryBuilder::for(Topic::class)
//            ->allowedIncludes('user', 'category')
//            ->allowedFilters([
//                'title',
//                AllowedFilter::exact('category_id'),
//                AllowedFilter::scope('withOrder')->default('recentReplied'),
//            ])
//            ->paginate();

        return TopicResource::collection($topics);

    }

    /**
     * 某个用户话题列表
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function userIndex(Request $request, User $user, TopicQuery $query)
    {
        $topics = $query->where('user_id', $user->id)->paginate();
//        $query = $user->topics()->getQuery();

//        $topics = QueryBuilder::for($query)
//            ->allowedIncludes('user', 'category')
//            ->allowedFilters([
//                'title',
//                AllowedFilter::exact('category_id'),
//                AllowedFilter::scope('withOrder')->default('recentReplied'),
//            ])
//            ->paginate();

        return TopicResource::collection($topics);
    }


    /**
     * 创建话题
     * @param TopicRequest $request
     * @param Topic $topic
     * @return TopicResource
     */
    public function store(TopicRequest $request, Topic $topic)
    {
        $topic->fill($request->all());
        $topic->user_id = $request->user()->id;
        $topic->save();

        return new TopicResource($topic);
    }

    /**
     * 修改话题
     * @param TopicRequest $request
     * @param Topic $topic
     * @return TopicResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(TopicRequest $request, Topic $topic)
    {
        $this->authorize('update', $topic);

        $topic->update($request->all());
        return new TopicResource($topic);
    }

    /**
     * 删除话题
     * @param Topic $topic
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Topic $topic)
    {
        $this->authorize('destroy', $topic);

        $topic->delete();

        return response(null, 204);
    }

    public function show($topicId, TopicQuery $query)
    {

        $topic = $query->findOrFail($topicId);
//        $topic = QueryBuilder::for(Topic::class)
//            ->allowedIncludes('user', 'category')
//            ->findOrFail($topicId);

        return new TopicResource($topic);
    }
}

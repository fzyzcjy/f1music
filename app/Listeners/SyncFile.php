<?php

namespace App\Listeners;

use Log;
use App\File;
use App\Song;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncFile
{
    public function onSongDeleted($event)
    {
Log::info('OnDeletedSong'.$event->song->id);
        if ($event->song->isForceDeleting()) {
Log::info('FDeleted'.$event->song->file_id);
            if ($event->song->file->songs->count() == 0) {
            //文件无已关联的曲目,则彻底删除
Log::info('FileNotexists'.$event->song->file_id);
                File::withTrashed()->find($event->song->file_id)->forceDelete(); //存储系统的文件同步由FileDeleting事件触发
            }
        }
    }

    public function onFileDeleting($event)
    {
Log::info('OnDeletingFile');
        if ($event->file->isForceDeleting()) { // 是彻底删除,则删除存储文件
            if ($event->file->songs->count() == 0) { // 无关联的活跃曲目 TODO
                Log::info('ForceDeleted'.$event->file->id);
                Storage::disk('public')->delete($event->file->md5.'.mp3');
            } else {
                return false; // 停止删除操作
            }
        }
    }

    public function subscribe($events)
    {
        $events->listen(
            'App\Events\SongDeleted',
            'App\Listeners\SyncFile@onSongDeleted'
        );
        $events->listen(
            'App\Events\FileDeleting',
            'App\Listeners\SyncFile@onFileDeleting'
        ); 
    }
}

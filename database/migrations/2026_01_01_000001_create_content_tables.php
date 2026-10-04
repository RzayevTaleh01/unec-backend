<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->string('url')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('journal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->json('roles');
            $table->unique(['user_id', 'journal_id']);
        });

        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('volume');
            $table->unsignedSmallInteger('number');
            $table->unsignedSmallInteger('year');
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->string('cover')->nullable();
            $table->string('pdf')->nullable();
            $table->string('doi')->nullable();
            $table->date('published_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['volume', 'number', 'year']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('title');
            $table->json('abstract')->nullable();
            $table->json('keywords')->nullable();
            $table->string('pdf')->nullable();
            $table->string('doi')->nullable();
            $table->string('language', 8)->default('az');
            $table->string('pages')->nullable();
            $table->string('status')->default('published')->index();
            $table->date('published_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('article_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('institution')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('body')->nullable();
            $table->string('image')->nullable();
            $table->date('published_at');
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('editorial_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('role');
            $table->string('photo')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('name');
            $table->json('organization')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug');
            $table->json('title');
            $table->json('body')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['page_section_id', 'slug']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(true);
            $table->unique(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        foreach (['notification_settings', 'settings', 'pages', 'page_sections', 'contacts', 'editorial_members', 'announcements', 'article_authors', 'articles', 'issues', 'journal_user', 'journals'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

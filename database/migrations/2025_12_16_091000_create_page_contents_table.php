<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique(); // e.g., 'about'
            $table->json('content'); // Store the content as JSON
            $table->unsignedBigInteger('updated_by')->nullable(); // Admin who updated it
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Insert default about page content
        DB::table('page_contents')->insert([
            'page_key' => 'about',
            'content' => json_encode([
                'hero_title' => 'قوة الأمل',
                'hero_subtitle' => 'من محارب السرطان إلى بائع الأحذية الإلكتروني',
                'hero_name' => 'البطل: سمير صافي الحاتمي',
                'hero_description' => 'محارب سرطان',
                'main_quote' => 'الحمد لله الذي منّ علي بقوة الأمل...',
                'story_paragraph_1' => 'قصتي بدأت عندما واجهت أعتى محنة في حياتي: مرض السرطان. الدواء الذي أحتاجه يتكلف أكثر من ٤٥٠٠$ للجرعة الواحدة! طرقت كل أبواب الحكومة... لكن دون استجابة. لم أستسلم.',
                'story_paragraph_2' => 'قررت أن أكون يدي التي تمتد لنفسي ولعائلتي...',
                'story_paragraph_3' => 'فأسست مشروعي الصغير: متجر إلكتروني لتسويق الأحذية.',
                'goal_text' => 'توفير مصاريف علاجي ومصاريف جامعتي (أنا طالب مرحلة رابعة).',
                'feature_1_title' => 'أسعار مخفضة جداً',
                'feature_1_description' => 'لأني لا أدفع إيجار محل',
                'feature_2_title' => 'توصيل لكل العراق',
                'feature_2_description' => 'بسعر رمزي'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('page_contents');
    }
};

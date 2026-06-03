<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('ayurvexa'));

use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewGeneratorService;
use Illuminate\Support\Facades\DB;

$generator = app(ReviewGeneratorService::class);

// Delete ALL generated reviews
$deleted = DB::table('reviews')->where('is_generated', true)->delete();
echo "Deleted {$deleted} generated reviews.\n";

// Product-specific review pools keyed by product ID
$reviewPools = [
    // ===== Energize Q – Natural Stamina Booster (ID=5) =====
    5 => [
        'titles' => [
            'Energy levels are through the roof!',
            'Replaced my morning coffee with this',
            'Stamina has improved noticeably',
            'No more midday crashes',
            'Best natural energy booster I have tried',
            'Ashwagandha + Shilajit combo is powerful',
            'Feel active the entire day now',
            'Great for gym and work stamina',
            'Finally found something that actually works',
            'Sustained energy without jitters',
            'My go-to supplement for busy days',
            'Noticed the difference in just 2 weeks',
            'Highly recommend for professionals',
            'Clean energy that lasts all day',
            'Perfect for an active lifestyle',
        ],
        'contents' => [
            'I have been taking Energize Q for about a month now and the difference is incredible. No more afternoon crashes at work. I feel consistently energetic throughout the day without any jittery feeling.',
            'As a software developer who works long hours, fatigue was a constant struggle. Energize Q has genuinely helped me stay focused and active. The Ashwagandha and Shilajit combination works really well.',
            'Replaced my 3 cups of coffee with these tablets and honestly feel much better. The energy is clean and sustained, not the spike-and-crash cycle I was used to with caffeine.',
            'Started taking this for gym performance and it has exceeded my expectations. My endurance during workouts has improved and I recover faster. Genuinely impressed with the formulation.',
            'My husband and I both take Energize Q daily. He noticed improved stamina at work and I feel less exhausted by evening. Great product for both men and women.',
            'I was skeptical about natural energy supplements but Energize Q proved me wrong. Within 2 weeks I noticed I was not reaching for that 4 PM chai anymore. Real, sustained energy.',
            'The fact that this has Swarna Bhasma along with Ashwagandha really sets it apart. I have tried many stamina boosters but this one actually delivers on its promise.',
            'Taking 2 tablets in the morning with breakfast. My productivity at work has gone up significantly. No more brain fog or lethargy after lunch.',
            'Bought this after reading about the ingredients. I am 45 and was feeling constant fatigue. After 3 weeks of Energize Q, I feel like I am in my 30s again. Excellent formulation.',
            'This is my third bottle. Consistent quality every time. I take it before my morning walk and the endurance improvement is very noticeable. Highly recommend.',
            'I work night shifts at a hospital and Energize Q has been a lifesaver. I feel alert and energetic through my entire shift. No crash, no side effects.',
            'Best stamina booster I have found in India. Pure Ayurvedic ingredients, no chemicals. I can feel the difference in my daily energy levels. Will keep ordering.',
            'My father is 60 and started taking these tablets. He says he feels more active during his evening walks. Even his morning lethargy has reduced. Very happy with the results.',
            'Great for anyone dealing with burnout. I was exhausted from my corporate job and this supplement gave me my energy back. Clean, effective, and affordable.',
            'Ordered for the second time. Excellent product. The energy boost is gradual and natural, not like those sugar-loaded energy drinks. Perfect for daily use.',
            'Noticed improved stamina during yoga sessions. I can hold poses longer and feel less fatigued after practice. The herbal ingredients really make a difference.',
            'I compared this with 3 other energy supplements and Energize Q has the best ingredient profile. Shilajit, Ashwagandha, and the other herbs work synergistically. Very satisfied.',
            'Took about 10 days to feel the full effect but once it kicked in, the energy improvement was significant. I can work out harder and still have energy for evening activities.',
            'My wife gifted me this on my birthday. Best gift ever! I feel so much more energetic and my mood has improved too. The adrenal support really helps with stress.',
            'No artificial stimulants, no side effects, just clean natural energy. This is what a stamina booster should be. Five stars from me.',
        ],
        'pros' => [
            'Sustained energy without crashes', 'Natural Ayurvedic ingredients', 'Improved stamina and endurance',
            'No jitters or side effects', 'Easy to swallow tablets', 'Noticeable results in 2 weeks',
            'Good for both gym and work', 'Contains Shilajit and Ashwagandha', 'Fast delivery',
            'GMP certified and safe', 'Reduces afternoon fatigue', 'Better focus and clarity',
        ],
    ],

    // ===== CoQ10Life – CoQ10 Tablets for Heart Health (ID=4) =====
    4 => [
        'titles' => [
            'Heart health has genuinely improved',
            'My cardiologist recommended CoQ10',
            'Essential supplement for anyone over 40',
            'Blood pressure readings are better now',
            'Great for heart and energy both',
            'Best CoQ10 supplement I have found in India',
            'Taking it with statins — feel much better',
            'Noticeable improvement in cardiovascular health',
            'Energy levels improved along with heart health',
            'Quality CoQ10 at a fair price',
            'Doctor approved and it actually works',
            'Feeling stronger and more energetic',
            'Heart palpitations reduced significantly',
            'A must for anyone on cholesterol medication',
            'Excellent quality and absorption',
        ],
        'contents' => [
            'My cardiologist suggested I take CoQ10 since I am on statins. After 2 months on CoQ10Life, my energy levels have improved dramatically. Statins were draining me but this has fixed that. Excellent product.',
            'I am 52 and was experiencing frequent fatigue and shortness of breath. My doctor recommended CoQ10 supplements. After taking CoQ10Life for 6 weeks, I feel noticeably stronger during walks and daily activities.',
            'The combination of CoQ10 with Omega-3 and L-Carnitine in this formula is impressive. I can feel the difference in my cardiovascular health. Morning walks are easier and I have more stamina.',
            'Started taking this for heart health after my annual checkup showed high LDL. Along with diet changes, CoQ10Life has helped me feel more energetic. My next checkup showed improved numbers.',
            'Both my parents take this daily. My mother says her fatigue has reduced and father says his blood pressure readings have been more stable. Great product for elderly heart care.',
            'I researched CoQ10 supplements extensively before choosing this one. The bioavailability of this formulation is excellent. You can actually feel the difference within 3-4 weeks of daily use.',
            'Taking 1 tablet daily after lunch with some nuts for fat absorption as suggested. My energy levels have improved and I no longer feel that heavy feeling in my chest during exercise.',
            'Best investment in my heart health. I have been using CoQ10Life for 3 months now. My cardiologist noticed improvement in my overall cardiovascular markers. Will continue taking this.',
            'I was experiencing occasional heart palpitations and my doctor recommended CoQ10. After 6 weeks on this supplement, the palpitations have reduced significantly. Very grateful for this product.',
            'Vegetarian, clean label, and GMP certified. These were important factors for me. The product itself works wonderfully for heart health. I feel more energetic and my circulation seems better.',
            'My husband is 58 and has been on heart medication. His doctor added CoQ10 to his regimen and we chose this brand. He feels less tired and his exercise tolerance has improved noticeably.',
            'Excellent CoQ10 supplement. I compared the ingredients with imported brands costing 3x more and this matches them perfectly. Indian brand at Indian prices with international quality.',
            'I take this daily for preventive heart care. At 44, I want to ensure my heart stays healthy. The Lycopene and CoQ10 combination provides excellent antioxidant protection.',
            'After trying 2 other CoQ10 brands with no results, I switched to CoQ10Life. The difference was noticeable within 3 weeks. Better energy, less breathlessness, and overall improved wellbeing.',
            'Clean formulation with no unnecessary fillers. Each tablet is well-formulated with the right dosage. My doctor reviewed the ingredients and approved it for my daily regimen.',
            'I started this after my angiography showed early blockage signs. Along with medication, CoQ10Life has been a valuable addition. I feel more confident about my heart health now.',
            'My mother has been taking this for 4 months. Her stamina has improved remarkably. She can climb stairs without getting winded now. The cellular energy boost is real.',
            'Good absorption, no stomach issues, and genuine results. This CoQ10 supplement works exactly as described. I take it religiously every day and my heart health checkups have been good.',
            'Purchased for my father-in-law who had bypass surgery. His cardiologist recommended CoQ10. This brand has clean ingredients and he has noticed improved energy levels.',
            'The L-Carnitine and CoQ10 combination is scientifically sound for heart health. I am a pharmacist and I recommend this to my customers. Quality product at a reasonable price.',
        ],
        'pros' => [
            'Improved cardiovascular health', 'Better energy and stamina', 'Contains CoQ10 + Omega-3 + L-Carnitine',
            'Great for statin users', 'Vegetarian and clean label', 'Noticeable results in 3-4 weeks',
            'Doctor recommended', 'Good bioavailability', 'GMP certified manufacturing',
            'Supports blood pressure management', 'No side effects', 'Fair pricing',
        ],
    ],

    // ===== Ayurvexa Spirulina Bliss (ID=3) =====
    3 => [
        'titles' => [
            'Skin is visibly clearer and glowing',
            'Best spirulina tablets I have tried',
            'Immunity has improved noticeably',
            'Great source of plant-based protein',
            'Detox effect is real — skin cleared up',
            'Hair and skin both improved',
            'Energy and immunity boost in one tablet',
            'Organic spirulina at a great price',
            'My nutritionist recommended these',
            'Feeling healthier overall',
            'Spirulina really is a superfood',
            'Perfect daily supplement for vegetarians',
            'Visible skin improvement in 3 weeks',
            'No more frequent colds and coughs',
            'Clean, pure, and effective spirulina',
        ],
        'contents' => [
            'I started taking Spirulina Bliss for my skin issues. After about 3 weeks, I noticed my acne has reduced and my skin looks much clearer. The detoxifying effect is genuine.',
            'As a vegetarian, getting enough protein was always a concern. These spirulina tablets solve that problem. 60% protein content plus all essential amino acids. My energy levels have also improved.',
            'My dermatologist suggested spirulina for my dull skin. After 2 months on these tablets, my complexion has genuinely brightened. Friends have noticed the difference and keep asking what I am using.',
            'I used to catch cold every other month. Since starting Spirulina Bliss, my immunity has noticeably strengthened. Have not fallen sick in 4 months now. The antioxidant content really works.',
            'Bought these after comparing several spirulina brands. The quality of Ayurvexa is clearly superior. Tablets are easy to swallow and I have seen real improvement in my skin texture and energy.',
            'My hair was falling excessively due to nutritional deficiency. My doctor added spirulina to my routine. After 6 weeks on these tablets, hair fall has reduced significantly and new growth is visible.',
            'Taking 2 tablets on empty stomach every morning. My digestion has improved, skin looks healthier, and I generally feel more energetic. The chlorophyll content really helps with detoxification.',
            'I have tried powder spirulina before but the taste was unbearable. These tablets are so much easier. Same benefits without the awful taste. And the results are visible on my skin.',
            'My nutritionist recommended organic spirulina for my iron deficiency. These tablets have helped bring my levels up along with prescribed supplements. Great natural source of minerals.',
            'Impressed with the purity. No fillers, no binding agents, just pure spirulina. Lab-tested and organic. My skin has a natural glow now that I did not have before. Very satisfied.',
            'Using these for 3 months now. My skin has cleared up, energy levels are up, and I feel overall healthier. This is one supplement I will never stop taking.',
            'Gifted this to my mother for her joint pain and immunity. She loves it. Says she feels more active and her skin looks better too. Great for elderly health support.',
            'The phycocyanin and GLA content in spirulina is amazing for skin. I noticed reduced inflammation and more even skin tone after about a month. Science-backed superfood.',
            'Perfect supplement for anyone living in a polluted city. The detox benefits of spirulina help your body cope with environmental toxins. I feel lighter and my skin reflects that.',
            'I take these before my morning workout. The plant protein helps with muscle recovery and the overall nutritional profile keeps me going strong. Better than any synthetic pre-workout.',
            'My entire family takes Spirulina Bliss now. My kids have fewer sick days, my wife loves the skin benefits, and I appreciate the energy boost. One tablet that does so much.',
            'Organic, lab-tested, cold-pressed. Everything I look for in a spirulina supplement. The quality is world class at an Indian price point. Highly recommended.',
            'Started seeing skin improvements within 2 weeks itself. The glow is subtle but definitely there. My colleagues have started noticing too. Will reorder for sure.',
            'Best spirulina in the market hands down. I have used 4 different brands over the years and Ayurvexa delivers the best results for skin and immunity both.',
            'Took this during a detox phase and the results were remarkable. My skin cleared up, bloating reduced, and I felt genuinely cleansed from inside. Fantastic product.',
        ],
        'pros' => [
            'Visible skin improvement', 'Boosts immunity effectively', 'Rich in plant-based protein',
            'Organic and lab-tested', 'Easy to swallow tablets', 'Effective detoxification',
            'Contains all essential amino acids', 'Good for hair health too', 'Cold-pressed for purity',
            'No artificial additives', 'Improved energy levels', 'Great for vegetarians',
        ],
    ],

    // ===== Ayurvexa Skin Sculpt (ID=2) =====
    2 => [
        'titles' => [
            'My skin is glowing like never before',
            'Collagen + Glutathione combo is amazing',
            'Fine lines have visibly reduced',
            'Best skin supplement I have ever used',
            'Hydration has improved dramatically',
            'Brighter complexion in just 4 weeks',
            'My dermatologist approved this formula',
            'Skin feels plumper and more youthful',
            'Worth every rupee for skin health',
            'Better than expensive collagen drinks',
            'Real results — not just marketing',
            'Hyaluronic acid makes the difference',
            'Anti-aging from within actually works',
            'My skin texture has transformed',
            'Five-star supplement for skin radiance',
        ],
        'contents' => [
            'I have been taking Skin Sculpt for 2 months and the results are incredible. My skin looks plumper, more hydrated, and the fine lines around my eyes have visibly reduced. The collagen and hyaluronic acid combination is powerful.',
            'At 38, my skin was losing its firmness and glow. My dermatologist recommended an oral collagen supplement and I chose Skin Sculpt. After 6 weeks, my skin feels firmer and colleagues keep asking about my skincare routine.',
            'The 5-in-1 formula is what convinced me to try this. Collagen, Glutathione, Hyaluronic Acid, Vitamin C and E — everything my skin needs in one tablet. The results speak for themselves. My complexion has brightened noticeably.',
            'I spent lakhs on topical creams and serums with minimal results. Skin Sculpt taught me that true skin health comes from within. After 3 months, my skin is more radiant than it has been in years.',
            'The L-Glutathione in this supplement has noticeably evened out my skin tone. I had pigmentation issues around my cheeks and they have faded considerably. Plus the overall glow is something I never achieved with just topicals.',
            'Taking this religiously every day after breakfast. My husband noticed the change before I did — he said my skin looks younger and fresher. The hyaluronic acid really helps with hydration.',
            'I am a bride-to-be and started this 3 months before my wedding. My skin has transformed completely. Clearer, brighter, and so much more hydrated. My makeup artist was impressed with my skin quality.',
            'Tried many collagen supplements before this one. The difference with Skin Sculpt is the comprehensive formula. It is not just collagen — the glutathione and Vitamin C boost the results significantly.',
            'My mother is 55 and her skin was looking very dull and saggy. I got her Skin Sculpt and after 2 months, even she admits her skin feels different. More firm and the dark spots have lightened.',
            'As a dermatologist, I can say this formula is well-designed. Type-1 Collagen with Vitamin C for synthesis, Glutathione for brightening, and HA for hydration. I recommend this to my patients.',
            'The dryness I used to experience on my cheeks is completely gone. My skin feels dewy and supple throughout the day. Hyaluronic acid supplements really do work and this brand delivers quality.',
            'Visible reduction in fine lines after 8 weeks of consistent use. I take photos every 2 weeks to track progress and the improvement is undeniable. Best anti-aging supplement I have tried.',
            'My skin used to look tired and dull no matter how much sleep I got. Skin Sculpt has changed that. I look refreshed and radiant now. The cellular rejuvenation is real.',
            'I compared this with imported collagen brands costing 3-4x more. Same key ingredients, same dosage, much better price. Ayurvexa has nailed the formulation. Indian brand, international quality.',
            'Started noticing brighter skin within 3 weeks. By week 6, my friends were asking what product I was using. The Vitamin E protection really helps those of us exposed to sun daily.',
            'Post-pregnancy, my skin had lost all its glow and elasticity. Skin Sculpt helped me regain it. After 3 months, my skin looks and feels like it did before pregnancy. Extremely grateful.',
            'The fact that it is GMP certified and clean label gives me confidence in what I am putting in my body. Results are visible — firmer skin, better hydration, and a natural glow.',
            'I take this along with a good diet and water intake. The combination has transformed my skin from dull and patchy to clear and radiant. Collagen from within is the way to go.',
            'My sister recommended this and now I am recommending it to everyone. The brightening effect of Glutathione combined with the plumping effect of Hyaluronic Acid is chef kiss.',
            'Four months in and I can confidently say this is the best skincare investment I have made. Better than any serum or cream I have tried. Inside-out beauty is the real deal.',
        ],
        'pros' => [
            'Visible skin brightening', 'Collagen + Glutathione + HA formula', 'Reduced fine lines and wrinkles',
            'Improved skin hydration', 'Even skin tone', 'Contains Vitamin C and E',
            'Clean and GMP certified', 'Results visible in 3-4 weeks', 'Better than topical treatments',
            'Suitable for all skin types', 'Easy once-a-day dosage', 'No side effects',
        ],
    ],

    // ===== 360° Energy & Detox Reset Combo (ID=8) =====
    8 => [
        'titles' => [
            'Detox + energy — perfect combination',
            'Feel lighter and more energetic',
            'My body feels completely reset',
            'Best combo for a fresh start',
            'Liver support + stamina boost works great together',
            'Digestion improved and energy is up',
            'Great value combo — buying again',
            'Solved my fatigue and bloating issues',
            'The detox effect is genuinely noticeable',
            'Full body reset in 30 days',
        ],
        'contents' => [
            'This combo is brilliant. The liver support cleans your system while Energize Q keeps your energy levels high. After 4 weeks, I feel lighter, more active, and my digestion has improved significantly.',
            'I was dealing with constant bloating and fatigue. This combo addressed both problems. The detox tablets cleaned up my gut and the energy booster kept me from feeling drained during the detox process.',
            'Bought this as a 30-day reset and it delivered. My skin is clearer because the liver detox is working, and I have more energy than before. The two products complement each other perfectly.',
            'The 15% saving on the combo makes this an excellent deal. Both products are high quality. I take the liver support after dinner and Energize Q in the morning. Feel fantastic.',
            'After a month of poor eating during festivals, I needed a reset. This combo was exactly what my body needed. Digestion improved within a week and sustained energy returned by week 2.',
            'I recommended this to my colleague who was feeling sluggish. He messaged me after 3 weeks saying he feels like a different person. The detox and energy combo really works synergistically.',
            'My metabolism feels optimized. The liver detox tablets handle the cleansing while the stamina booster ensures I am not feeling weak during the process. Smart formulation, great results.',
            'Second time ordering this combo. First time I noticed visible improvements in energy and digestion. This time it is for maintenance. Excellent products, especially together.',
            'The liver support alone made a huge difference in my bloating and morning sluggishness. Combined with Energize Q, I feel clean and energetic all day. Best health investment.',
            'Tried liver detox supplements before but always felt drained during the process. Energize Q solves that problem completely. You get the detox benefits without losing your energy.',
            'This combo helped me get back on track after months of irregular eating and too much junk food. My body feels cleaner and my energy levels are stable throughout the day.',
            'Excellent value for money. Getting two complementary supplements at a discount is smart. Both products deliver on their promises. Will continue using this combo long-term.',
        ],
        'pros' => [
            'Detox and energy in one combo', 'Improved digestion', 'Sustained energy during detox',
            'Clearer skin from liver detox', '15% savings on combo', 'Reduced bloating',
            'Clean natural ingredients', 'Complementary formulations', 'Great for a body reset',
        ],
    ],

    // ===== Anti-Aging & Skin Repair Combo (ID=9) =====
    9 => [
        'titles' => [
            'Skin looks younger and heart feels stronger',
            'Anti-aging from inside — finally something that works',
            'CoQ10 + Skin Sculpt is the perfect pair',
            'Visible skin repair and more energy',
            'Best anti-aging combo available in India',
            'My skin and energy both transformed',
            'Cellular level rejuvenation — you can feel it',
            'Skin firmness improved, fine lines fading',
            'Great combo for women over 35',
            'Youthful skin and healthy heart together',
        ],
        'contents' => [
            'This combo tackles aging on two fronts. Skin Sculpt is repairing my skin from within — fewer wrinkles, better glow — while CoQ10Life is giving me the cellular energy I was missing. I feel and look younger.',
            'At 42, I was noticing rapid skin aging and low energy. This combo has been a game-changer. After 2 months, my fine lines have reduced, skin is firmer, and I have significantly more energy throughout the day.',
            'The science behind this combo makes sense. CoQ10 handles cellular energy and heart health while Skin Sculpt rebuilds collagen and hydration. Together, they address the root causes of aging. Brilliant pairing.',
            'My wife and I both take this combo. She loves the skin benefits — her complexion has brightened noticeably. I appreciate the heart health and energy aspects. Win-win for both of us.',
            'Bought this for anti-aging and got so much more. My cardiovascular stamina during walks has improved and my skin genuinely looks younger. The 15% combo discount is a nice bonus too.',
            'I have spent a lot on anti-aging creams and serums. This combo taught me that aging happens at the cellular level. After 3 months, my skin is more radiant and I feel more vital than I did 5 years ago.',
            'Perfect combo for anyone entering their 40s. Skin repair and heart protection in two simple tablets daily. My dermatologist and cardiologist both approved these supplements.',
            'The Glutathione in Skin Sculpt for brightening combined with CoQ10 for energy gives you a youthful look AND feel. This is not just about looking younger — you genuinely feel younger.',
            'Three months in and the results are clear. Fewer fine lines, plumper skin, better energy, and my heart checkup was great. This combo addresses aging holistically.',
            'My mother-in-law is 58 and this combo has made a visible difference. Her skin looks healthier, she has more energy for her grandkids, and she says she feels more active overall.',
            'The combo discount makes this very affordable for what you get. Two premium supplements working together for anti-aging. Results are visible and sustained. Highly recommended.',
            'I track my skin with monthly photos and the improvement since starting this combo is undeniable. Firmer jawline, fewer crow feet, brighter complexion. The internal approach to anti-aging works.',
        ],
        'pros' => [
            'Anti-aging for skin and body', 'Skin repair + heart health', 'Visible reduction in fine lines',
            'Improved energy levels', 'CoQ10 + Collagen synergy', '15% savings on combo',
            'Suitable for men and women', 'Holistic approach to aging', 'Clean and safe formulation',
        ],
    ],

    // ===== Ultimate Detox & Skin Glow Combo (ID=7) =====
    7 => [
        'titles' => [
            'Detox cleared my skin like nothing else',
            'Liver health = skin health — this combo proves it',
            'Glowing skin starts from a healthy liver',
            'Best combo for acne-prone skin',
            'My skin transformed from inside out',
            'Detox + glow — exactly what I needed',
            'Clearer skin and better digestion',
            'This combo is a game-changer for skin',
            'My acne reduced after liver detox',
            'Radiant skin through internal cleansing',
        ],
        'contents' => [
            'I never realized how much my liver health affected my skin until I tried this combo. The liver support cleared toxins causing my breakouts, and Skin Sculpt rebuilt my skin quality. My complexion is completely different now.',
            'Dealing with stubborn acne for years. A naturopath told me to detox my liver first, then nourish my skin. This combo does exactly that. After 2 months, my acne has reduced by 70% and skin is glowing.',
            'The logic of this combo is perfect — clean your system first with liver detox, then nourish your skin with collagen and antioxidants. My skin has never looked this clear and radiant. Best purchase ever.',
            'My skin was always dull despite using expensive skincare products. This combo taught me the problem was internal. After liver detox and skin nourishment from within, my complexion has truly transformed.',
            'I love how these two products complement each other. The liver support handles bloating and toxin removal while Skin Sculpt gives me the glow. My digestion improved too which reflects on my skin.',
            'Three months on this combo and my friends think I got a facial treatment done. The truth is — healthy liver equals healthy skin. The combo discount makes it very affordable for the results you get.',
            'My dermatologist could not figure out why my skin was so dull despite no specific skin disease. She suggested liver detox. This combo was perfect — liver health tablets plus skin nourishment.',
            'If you have hormonal acne or dull skin, try this combo before expensive treatments. Detoxifying your liver makes a massive difference. Add Skin Sculpt for collagen and glow — results guaranteed.',
            'Bought for my wife who had pigmentation issues. The liver detox helped clear toxins and the glutathione in Skin Sculpt brightened her complexion. She is very happy with the results.',
            'I have been on this combo for 4 months now. My skin is clearer, brighter, and more hydrated. The liver support also improved my digestion significantly. Holistic health at its best.',
            'The connection between gut health, liver function, and skin quality is real. This combo addresses all three. My skin went from acne-prone and dull to clear and radiant in about 8 weeks.',
            'Second purchase. The first round cleared my skin issues and improved my digestion. Now I am continuing for maintenance. The combo is excellent value for money.',
        ],
        'pros' => [
            'Clearer skin through detox', 'Liver health improves skin quality', 'Reduced acne and breakouts',
            'Improved digestion and metabolism', 'Collagen + detox synergy', 'Brighter and even complexion',
            '15% savings on combo', 'Addresses root cause of dull skin', 'Visible results in 4-6 weeks',
        ],
    ],

    // ===== Heart Health & Vitality Boost Combo (ID=11) =====
    11 => [
        'titles' => [
            'Heart health and immunity in one combo',
            'CoQ10 + Spirulina is a smart pairing',
            'Feel stronger and healthier overall',
            'Great for daily cardiovascular care',
            'My complete daily wellness combo',
            'Heart protection + superfood nutrition',
            'Energy and immunity both improved',
            'Perfect health combo for men over 40',
            'Vitality has genuinely improved',
            'Comprehensive wellness support',
        ],
        'contents' => [
            'This combo covers my two biggest health priorities — heart health and immunity. CoQ10Life strengthens my cardiovascular system while Spirulina Bliss keeps my immunity strong. Feel healthier than I have in years.',
            'At 48, heart health and general wellness are my top concerns. This combo delivers on both. My energy levels are up, I fall sick less often, and my recent heart checkup was excellent.',
            'The spirulina provides all the micronutrients and antioxidants while CoQ10 keeps the heart pumping efficiently. Together, they create a comprehensive daily wellness system. Very impressed.',
            'My father takes this combo daily. His cardiologist is happy with his heart parameters and his general immunity has improved. He used to catch cold every season but not anymore.',
            'I chose this combo because it addresses cellular energy through CoQ10 and nutritional completeness through spirulina. Smart combination. My overall vitality has improved significantly.',
            'Great value combo for anyone serious about long-term health. The heart health supplement protects cardiovascular function while spirulina strengthens immunity and adds a skin glow bonus.',
            'Three months in and the results are clear — better energy, fewer sick days, improved stamina during walks, and even my skin looks healthier thanks to spirulina. This combo does it all.',
            'My wife got this for me after my cholesterol test. CoQ10 supports heart health while spirulina helps detoxify. Both products are high quality and the combo saves 15%. Win-win.',
            'If you want one combo that covers heart, immunity, energy, and nutrition, this is it. I have been taking it for 2 months and feel genuinely more vital and resilient.',
            'Both supplements in this combo are clean, vegetarian, and GMP certified. No compromise on quality. My body feels stronger and my immune system handles seasonal changes much better now.',
            'I compared this with buying both products separately and the combo saving is genuine. More importantly, the products work beautifully together. Heart health and daily wellness covered.',
            'Perfect preventive health combo. I am 45 and want to stay ahead of heart and immunity issues. This combo gives me confidence that I am taking care of my body proactively.',
        ],
        'pros' => [
            'Heart health + immunity support', 'CoQ10 and spirulina synergy', 'Improved overall vitality',
            'Better cardiovascular stamina', 'Stronger immune system', 'Nutrient-dense superfood included',
            '15% combo discount', 'Clean vegetarian formulation', 'Great for preventive health',
        ],
    ],

    // ===== Daily Wellness & Immunity Boost Combo (ID=10) =====
    10 => [
        'titles' => [
            'My daily health shield — immunity and energy',
            'Spirulina + Energize Q is the perfect pair',
            'Have not fallen sick since starting this',
            'Energy and immunity both sorted',
            'Best daily wellness combo for busy people',
            'Feels like a complete multivitamin plus energy',
            'My whole family takes this combo now',
            'Immunity boost + stamina boost in one',
            'No more seasonal colds and fatigue',
            'Smart combo for everyday health',
        ],
        'contents' => [
            'This combo is my daily health insurance. Spirulina handles nutrition and immunity while Energize Q keeps my energy high. Since starting this 2 months ago, I have not fallen sick once and my stamina is great.',
            'As a working mom, I need sustained energy and strong immunity. This combo delivers both. Spirulina nourishes my body with essential nutrients while Energize Q keeps fatigue at bay. Perfect for my lifestyle.',
            'I used to catch every cold and flu going around the office. Since starting this combo, my immunity has clearly strengthened. Plus the energy boost from Energize Q helps me stay productive all day.',
            'The spirulina provides the nutritional foundation and Energize Q builds the energy on top of it. Smart combination. My overall wellness has improved and I feel more resilient against daily stress.',
            'Bought this during monsoon season when I usually fall sick. Three months later and zero sick days. The immunity boost from spirulina combined with the stamina from Energize Q is remarkable.',
            'My husband and I both take this combo. He noticed improved gym performance and I love the immunity and skin benefits from spirulina. The energy boost helps us both manage our busy schedules.',
            'This combo replaced my multivitamin, energy drink, and immunity supplement. Three products in two tablets. The spirulina is a complete superfood and Energize Q is the best natural energy booster.',
            'Perfect combo for students and professionals. Spirulina keeps your nutrition and immunity in check while Energize Q ensures you stay sharp and energetic during long study or work sessions.',
            'I am a doctor and I recommend this combo to patients who complain of low energy and frequent illness. Natural, safe, and effective. Both supplements have clean labels with proper dosages.',
            'The 15% saving is nice but the real value is how well these two products work together. My overall wellness has improved — better energy, stronger immunity, and I even notice healthier skin.',
            'Switched from synthetic multivitamins to this natural combo and the difference is significant. I absorb the nutrients better, energy is more sustained, and my immunity is noticeably stronger.',
            'Best combo for anyone living in Indian cities with pollution and stress. Spirulina detoxifies while Energize Q fights the fatigue. Together they keep you healthy and active despite urban challenges.',
        ],
        'pros' => [
            'Daily energy + immunity support', 'Natural alternative to multivitamins', 'Reduced frequency of illness',
            'Sustained stamina throughout the day', 'Spirulina superfood nutrition', 'Herbal energy boost',
            '15% combo discount', 'Great for busy professionals', 'No artificial ingredients',
        ],
    ],

    // ===== Ayurvexa Liver Support (ID=1) =====
    1 => [
        'titles' => [
            'Digestion has improved dramatically',
            'Best liver detox supplement in India',
            'My bloating is finally gone',
            'Milk Thistle + NAC combo is powerful',
            'Liver health restored in 90 days',
            'Feel cleaner and lighter from inside',
            'Essential supplement for modern lifestyle',
            'Detox that actually works',
            'My liver enzymes improved significantly',
            'Excellent Ayurvedic liver support',
            'Metabolism boost after starting this',
            'Must-have for anyone who eats out often',
            'Skin cleared up as liver health improved',
            'Doctor recommended for fatty liver',
            'Quality detox supplement with real results',
        ],
        'contents' => [
            'I was diagnosed with mild fatty liver and my doctor recommended a liver support supplement along with diet changes. After 90 days on these tablets, my liver enzymes have significantly improved and I feel much lighter.',
            'The combination of Milk Thistle and NAC in this formula is exactly what medical research supports for liver health. As a healthcare professional, I am impressed with the formulation. My bloating has completely resolved.',
            'I eat out frequently due to my job and my digestion was suffering. Since starting Ayurvexa Liver Support, my digestion has improved dramatically. No more morning bloating or that heavy feeling after meals.',
            'After years of irregular eating and occasional drinking, I knew my liver needed help. These tablets have made a noticeable difference. My energy is better, bloating is gone, and my skin even looks clearer.',
            'The 11-ingredient formula is comprehensive. Milk Thistle for liver protection, NAC and Glutathione for detox, Turmeric and Licorice for inflammation. Every ingredient serves a purpose. Results are genuine.',
            'Three months in and my liver function tests are back to normal range. My gastroenterologist is pleased with the improvement. Along with diet changes, these tablets played a significant role.',
            'I take 2 tablets after dinner daily. Within 2 weeks, the morning sluggishness disappeared. By month 2, my digestion was notably better. By month 3, my doctor confirmed improved liver parameters.',
            'Living in a polluted city, liver detox is essential. These tablets help my body process environmental toxins better. I feel cleaner from inside and my skin has stopped breaking out.',
            'My father had elevated liver enzymes from years of medication. His doctor added liver support to his regimen. After 3 months on these tablets, his levels have come down to near normal.',
            'The Dandelion and Beetroot in this formula support bile production which really helps with fat digestion. Since starting this, I can eat a heavier meal without feeling terrible afterwards.',
            'Best liver detox supplement I have used. And I have tried several imported brands. The Ayurvedic herbs combined with modern ingredients like NAC give this a unique edge. Highly effective.',
            'My wife and I do a liver detox twice a year. This time we used Ayurvexa Liver Support and the results were better than any detox we have done before. Reduced bloating, better energy, clearer skin.',
            'I was experiencing skin breakouts and a naturopath traced it back to liver congestion. After 6 weeks on these tablets, my skin started clearing up. The liver-skin connection is real.',
            'The Ginger and Fenugreek in the formula really help with digestion. I used to feel bloated after every meal. Now my digestion is smooth and comfortable. Excellent formulation.',
            'Clean label, GMP certified, vegetarian, and genuinely effective. These liver support tablets check every box. My metabolism feels optimized and the detox benefits are clearly visible.',
            'Started taking this after Diwali season when my diet was terrible for weeks. Within 3 weeks, the bloating was gone and my energy returned. Now I keep it as a regular supplement.',
            'The triple action — detox, digestion, and metabolism — is what makes this special. Most liver supplements just claim detox. This one actually improves all three aspects of liver function.',
            'I am a yoga instructor and I recommend liver health to all my students. A clean liver means better energy, clearer skin, and improved digestion. This supplement delivers on all fronts.',
            'My liver ultrasound showed grade 1 fatty liver. Along with exercise and diet, I added these tablets. Six months later, my follow-up ultrasound showed significant improvement. Very grateful.',
            'Purchased for my mother who had poor digestion and constant bloating. She has been taking these for 2 months and says it is the best she has felt in years. Digestion is smooth now.',
        ],
        'pros' => [
            'Improved liver enzyme levels', 'Reduced bloating and gas', 'Contains Milk Thistle + NAC + Glutathione',
            'Better digestion after meals', 'Clearer skin from detox', 'Ayurvedic + modern formulation',
            'GMP certified and vegetarian', 'Visible results in 4-6 weeks', 'Improved metabolism',
            '11 active ingredients', 'Doctor recommended', 'No side effects',
        ],
    ],
];

$products = Product::where('is_active', true)->get();
$now = now();
$totalCreated = 0;

Review::withoutEvents(function () use ($products, $reviewPools, $generator, $now, &$totalCreated) {
    foreach ($products as $product) {
        $pool = $reviewPools[$product->id] ?? null;
        if (!$pool) {
            echo "  [SKIP] No review pool for product ID={$product->id} ({$product->name})\n";
            continue;
        }

        $titles = $pool['titles'];
        $contents = $pool['contents'];
        $pros = $pool['pros'];
        $reviews = [];

        for ($i = 0; $i < 30; $i++) {
            $daysAgo = mt_rand(7, 330);
            $createdAt = $now->copy()
                ->subDays($daysAgo)
                ->setTime(mt_rand(6, 23), mt_rand(0, 59), mt_rand(0, 59));

            // Pick random title and content (cycle through if fewer than 30)
            $title = $titles[$i % count($titles)];
            $content = $contents[$i % count($contents)];

            // Pick 2-4 random pros
            $shuffledPros = $pros;
            shuffle($shuffledPros);
            $reviewPros = array_slice($shuffledPros, 0, mt_rand(2, 4));

            $reviews[] = [
                'product_id' => $product->id,
                'user_id' => null,
                'guest_name' => $generator->randomIndianName(),
                'guest_email' => 'review' . mt_rand(10000, 99999) . '@customer.' . parse_url(config('app.url', 'localhost'), PHP_URL_HOST),
                'rating' => 5,
                'title' => $title,
                'content' => $content,
                'pros' => json_encode($reviewPros),
                'cons' => json_encode([]),
                'is_verified_purchase' => (bool) mt_rand(0, 1),
                'is_approved' => true,
                'is_featured' => $i === 0,
                'helpful_count' => mt_rand(0, 25),
                'unhelpful_count' => 0,
                'status' => 'approved',
                'is_generated' => true,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        DB::table('reviews')->insert($reviews);
        $totalCreated += count($reviews);

        // Update product rating & count
        $stats = DB::table('reviews')
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as review_count')
            ->first();

        $product->update([
            'rating' => round($stats->avg_rating, 2),
            'review_count' => $stats->review_count,
        ]);

        echo "  [OK] {$product->name} — {$stats->review_count} reviews\n";
    }
});

echo "\nDone! Created {$totalCreated} product-specific reviews.\n";

// Verify
echo "\n--- Verification ---\n";
foreach ($products as $p) {
    $samples = DB::table('reviews')->where('product_id', $p->id)->orderByDesc('created_at')->limit(2)->get(['guest_name','title']);
    echo "\n{$p->name}:\n";
    foreach ($samples as $r) {
        echo "  {$r->guest_name} — {$r->title}\n";
    }
}

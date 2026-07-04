<?php

namespace Database\Seeders;

use App\Models\BusinessSector;
use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessSectorSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $sortOrder => [$sectorName, $types]) {
            $sector = BusinessSector::firstOrCreate(
                ['name' => $sectorName],
                ['sort_order' => $sortOrder + 1],
            );

            foreach ($types as $typeOrder => $typeName) {
                BusinessType::firstOrCreate(
                    [
                        'business_sector_id' => $sector->id,
                        'name' => $typeName,
                    ],
                    ['sort_order' => $typeOrder + 1],
                );
            }
        }
    }

    /**
     * @return list<array{0: string, 1: list<string>}>
     */
    private function catalog(): array
    {
        return [
            ['KILIMO', [
                'Kilimo Cha Machungwa',
                'Kilimo Cha Maharage, Njegere, Kunde, Njugu Mawe',
                'Kilimo Cha Ngano Au Shairi',
                'Kilimo Cha Ulezi, Uwele Na Mtama',
                'Kilimo Cha Karanga',
                'Kilimo Cha Viazi Vitamuu',
                'Kilimo Cha Ndizi',
                'Kilimo Cha Miwa',
                'Kilimo Cha Embe',
                'Kilimo Cha Parachichi',
                'Kilimo Cha Matikiti Mapapai',
                'Kilimo Cha Kahawa',
                'Kilimo Cha Mkonge',
                'Kilimo Cha Tumbaku',
                'Kilimo Cha Mwani',
                'Kilimo Cha Viungo',
                'Kilimo Cha Korosho',
                'Kilimo Cha Kitalu Nyumba',
                'Kilimo Cha Chai',
                'Kilimo Cha Uyoga',
                'Kilimo Cha Pamba',
                'Kilimo Cha Ufuta',
                'Kilimo Cha Ngano',
                'Kilimo Cha Vanila',
                'Kilimo Cha Tangawizi',
                'Kilimo Cha Mahindi',
                'Kilimo Cha Mpunga',
                'Kilimo Cha Vitunguu',
                'Kilimo Cha Mihogo',
                'Kilimo Cha Choroko',
                'Wauzaji Wa Nafaka',
                'Wauzaji Wa Mboga, Nafaka, Matunda',
                'Wauzaji Wa Zana Na Pembejeo Za Kilimo',
                'Wauzaji Wa Viwatilifuti',
            ]],
            ['MIFUGO', [
                'Ufugaji Wa Ng\'ombe',
                'Ufugaji Wa Kondoo',
                'Ufugaji Wa Nguruwe',
                'Ufugaji Wa Kuku/Bata/Njiwa',
                'Ufugaji Wa Sugura',
                'Ufugaji Wa Mbuzi',
                'Uzalishaji Wa Maziwa',
                'Uzalishaji Wa Ngozi',
                'Uzalishaji Wa Mayai',
                'Uzalishaji Wa Kuku Wa Mayai/Nyama',
                'Ufugaji Wa Vipepeo, Panzi Na Mende',
                'Bucha Ya Nyama Ya Ngombe, Mbuzi, Kuku',
                'Kuzalisha Vyakula Vya Mifugo',
                'Maduka Ya Vyakula Na Pembejeo Za Mifugo',
            ]],
            ['UVUVI', [
                'Uvuvi Wa Samaki',
                'Usafirishaji Wa Samaki',
                'Biashara Ya Samaki',
                'Duka La Vifaa Vya Uvuvi',
                'Ufugaji Samaki Kwenye Mabwawa',
                'Boti Za Uvuvi',
                'Bucha Ya Samaki',
            ]],
            ['MISITU NA MALIASILI', [
                'Bustani Za Miti',
                'Mashamba Ya Miti',
                'Ufugaji Nyuki',
                'Biashara Ya Mazao Ya Nyuki',
                'Biashara Za Mbao',
                'Vitalu Vya Wanyama',
                'Utalii',
                'Uzalisha Wa Miche Ya Miti Na Matunda',
            ]],
            ['UJENZI', [
                'Kandarasi Za Ujenzi',
                'Ufundi Seremala',
                'Uchimbaji Na Uuzaji Wa Madini Ya Ujenzi',
                'Duka La Vifaa Vya Ujenzi',
                'Ufyatuaji Matofali',
                'Kufyeka Kando Ya Barabara',
                'Kutengeneza Vigae Na Mapambo',
                'Uchimbaji Wa Visima',
            ]],
            ['BIASHARA', [
                'Biashara Ya Chakula, Mikate, Maandazi, Keki',
                'Maduka Ya Jumla',
                'Maduka Ya Rejareja',
                'Biashara Ya Huduma Ndogo Ya Fedha',
                'Wakala Wa M Pesa, Tigo, Airtel',
                'Shajala (Stationary)',
                'Maduka Ya Madawa Muhimu Ya Binadamu',
                'Kuuza Vinywaji',
                'Kukodisha Maturubai, Viti, Meza',
                'Vipuli Na Vilaishi',
                'Kuuza Vifaa Vya Umeme',
                'Kuuza Nguo Za Mitumba',
                'Saluni Ya Kike/Kiume',
                'Ususi Wa Nywele / Mikeka',
                'Kuosha Magari',
                'Kuuza Dagaa',
                'Kuuza Genge',
                'Duka La Nguo',
                'Kuuza Matunda',
                'Duka La Viatu',
                'Duka La Urembo',
                'Sauna',
                'Kupaka Rangi Za Kucha Na Kushafisha Miguu',
                'Kuuza Nafaka',
                'Kuuza Vifaa Vya Simu',
            ]],
            ['MAZINGIRA', [
                'Kutunza Vitalu Ya Miti Na Mauwa',
                'Kupanda Miti Na Majani',
                'Kuuza Vifaa Vya Usafi Na Utunzaji Mazingira',
                'Kazi Za Usafi',
                'Kazi Za Kukusanya Takataka',
                'Kutunza Maeneo Ya Vyanzo Vya Maji',
                'Bustani Za Maua',
                'Kukodisha Magari Ya Maji Taka',
                'Kutunza Vitalu Vya Miti',
            ]],
            ['UTAMADUNI', [
                'Sanaa Za Maonesho',
                'Kuchonga Vinyago Na Mapambo',
                'Kutengeneza Silaha Za Jadi Kama Mikuki',
                'Kukodisha Vyombo Vya Muziki',
                'Wakala Wa Kazi Za Wasanii',
            ]],
            ['VIWANDA VIDOGO', [
                'Useremala',
                'Kufyatua Matofari',
                'Ujenzi Wa Nyumba',
                'Vifaa Vya Mapambo Ya Udongo',
                'Kutengeneza Sabuni',
                'Kutengeneza Magari',
                'Kutengeneza Chaki',
                'Kutengeneza Vinyago Na Mapambo',
                'Kufuma Mashuka, Vitambaa, Mabusati Na Mapambo',
                'Kusindika Vyakula Na Vinywaji',
                'Kuchomelea Madirisha Na Milango',
                'Kukoboa Na Kusaga Nafaa',
                'Kutengeneza Batiki',
                'Kusindika Unga Wa Lishe',
                'Kusindikaji Wa Ndizi',
                'Ufumaji Wa Masweta Na Kofia',
                'Utengenezaji Wa Chumvi',
                'Utengenezaji Wa Vikoi',
                'Utengenezaji Wa Mkaa Mbadala',
            ]],
            ['USAFIRISHAJI', [
                'Tax',
                'Bajaji',
                'Bodaboda',
                'Kusafirisha Mizigo',
                'Magari Ya Abiria',
                'Magari Ya Takataka',
                'Guta',
                'Kirikuu',
                'Boti',
                'Lori',
                'Magari Ya Mizigo',
                'Daladala',
            ]],
            ['AFYA', [
                'Zahanati',
                'Kliniki Ya Meno',
                'Kliniki Ya Macho',
                'Maabara',
            ]],
            ['MADINI', [
                'Uchimbaji Wa Mchanga',
                'Karasha La Kokoto',
                'Kubangua Na Kuuza Kokoto',
                'Mitambo Ya Uchimbaji Wa Madini',
                'Uzaji Wa Vifaa Vya Uchimbaji Wa Madini',
            ]],
        ];
    }
}

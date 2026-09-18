<?php

namespace Modules\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Customer\Models\Area;

class BangladeshAreaSeeder extends Seeder
{
    public function run(): void
    {
        $data = $this->getData();

        foreach ($data as $divisionName => $districts) {
            $division = Area::firstOrCreate(
                ['name' => $divisionName, 'level' => 'division', 'parent_id' => null],
                ['is_active' => true]
            );

            foreach ($districts as $districtName => $thanas) {
                $district = Area::firstOrCreate(
                    ['name' => $districtName, 'level' => 'district', 'parent_id' => $division->id],
                    ['is_active' => true]
                );

                foreach ($thanas as $thanaName) {
                    Area::firstOrCreate(
                        ['name' => $thanaName, 'level' => 'area', 'parent_id' => $district->id],
                        ['is_active' => true]
                    );
                }
            }
        }
    }

    private function getData(): array
    {
        return [
            'Dhaka' => [
                'Dhaka' => ['Adabor', 'Badda', 'Bangshal', 'Bimanbandar', 'Cantonment', 'Chalkbazar', 'Dakhshinkhan', 'Darus Salam', 'Demra', 'Dhanmondi', 'Gendaria', 'Gulshan', 'Hazaribagh', 'Jatrabari', 'Kadamtali', 'Kafrul', 'Kalabagan', 'Kamrangirchar', 'Khilgaon', 'Khilkhet', 'Kotwali', 'Lalbagh', 'Mirpur', 'Mohammadpur', 'Motijheel', 'New Market', 'Pallabi', 'Paltan', 'Ramna', 'Rampura', 'Sabujbagh', 'Shah Ali', 'Shahbagh', 'Sher-e-Bangla Nagar', 'Shyampur', 'Sutrapur', 'Tejgaon', 'Tejgaon Industrial Area', 'Turag', 'Uttara', 'Uttarkhan', 'Wari'],
                'Gazipur' => ['Gazipur Sadar', 'Kaliakair', 'Kaliganj', 'Kapasia', 'Sreepur', 'Tongi'],
                'Narayanganj' => ['Araihazar', 'Bandar', 'Narayanganj Sadar', 'Rupganj', 'Sonargaon'],
                'Manikganj' => ['Daulatpur', 'Ghior', 'Harirampur', 'Manikganj Sadar', 'Saturia', 'Shivalaya', 'Singair'],
                'Munshiganj' => ['Gazaria', 'Lohajang', 'Munshiganj Sadar', 'Sirajdikhan', 'Sreenagar', 'Tongibari'],
                'Narsingdi' => ['Belabo', 'Monohardi', 'Narsingdi Sadar', 'Palash', 'Raipura', 'Shibpur'],
                'Tangail' => ['Basail', 'Bhuapur', 'Delduar', 'Dhanbari', 'Ghatail', 'Gopalpur', 'Kalihati', 'Madhupur', 'Mirzapur', 'Nagarpur', 'Sakhipur', 'Tangail Sadar'],
                'Kishoreganj' => ['Austagram', 'Bajitpur', 'Bhairab', 'Hossainpur', 'Itna', 'Karimganj', 'Katiadi', 'Kishoreganj Sadar', 'Kuliarchar', 'Mithamain', 'Nikli', 'Pakundia', 'Tarail'],
                'Madaripur' => ['Kalkini', 'Madaripur Sadar', 'Rajoir', 'Shibchar'],
                'Faridpur' => ['Alfadanga', 'Bhanga', 'Boalmari', 'Charbhadrasan', 'Faridpur Sadar', 'Madhukhali', 'Nagarkanda', 'Sadarpur', 'Saltha'],
                'Gopalganj' => ['Gopalganj Sadar', 'Kashiani', 'Kotalipara', 'Muksudpur', 'Tungipara'],
                'Rajbari' => ['Baliakandi', 'Goalandaghat', 'Kalukhali', 'Pangsha', 'Rajbari Sadar'],
                'Shariatpur' => ['Bhedarganj', 'Damudya', 'Gosairhat', 'Naria', 'Shariatpur Sadar', 'Zajira'],
            ],
            'Chattogram' => [
                'Chattogram' => ['Anowara', 'Banshkhali', 'Boalkhali', 'Chandanaish', 'Fatikchhari', 'Hathazari', 'Lohagara', 'Mirsharai', 'Patiya', 'Rangunia', 'Raozan', 'Sandwip', 'Satkania', 'Sitakunda', 'Double Mooring', 'Kotwali', 'Pahartali', 'Panchlaish'],
                "Cox's Bazar" => ['Chakaria', "Cox's Bazar Sadar", 'Kutubdia', 'Maheshkhali', 'Pekua', 'Ramu', 'Teknaf', 'Ukhia'],
                'Comilla' => ['Barura', 'Brahmanpara', 'Burichang', 'Chandina', 'Chauddagram', 'Comilla Adarsha Sadar', 'Comilla Sadar Dakshin', 'Daudkandi', 'Debidwar', 'Homna', 'Laksam', 'Langalkot', 'Meghna', 'Monohorgonj', 'Muradnagar', 'Nangalkot', 'Titas'],
                'Noakhali' => ['Begumganj', 'Chatkhil', 'Companiganj', 'Hatiya', 'Kabirhat', 'Noakhali Sadar', 'Senbagh', 'Sonaimuri', 'Subarnachar'],
                'Feni' => ['Chhagalnaiya', 'Daganbhuiyan', 'Feni Sadar', 'Fulgazi', 'Parshuram', 'Sonagazi'],
                'Lakshmipur' => ['Kamalnagar', 'Lakshmipur Sadar', 'Raipur', 'Ramganj', 'Ramgati'],
                'Chandpur' => ['Chandpur Sadar', 'Faridganj', 'Haimchar', 'Haziganj', 'Kachua', 'Matlab Dakshin', 'Matlab Uttar', 'Shahrasti'],
                'Brahmanbaria' => ['Akhaura', 'Ashuganj', 'Bancharampur', 'Bijoynagar', 'Brahmanbaria Sadar', 'Kasba', 'Nabinagar', 'Nasirnagar', 'Sarail'],
                'Rangamati' => ['Baghaichhari', 'Barkal', 'Belaichhari', 'Juraichhari', 'Kaptai', 'Kawkhali', 'Langadu', 'Naniarchar', 'Rajasthali', 'Rangamati Sadar'],
                'Khagrachhari' => ['Dighinala', 'Khagrachhari Sadar', 'Lakshmichhari', 'Mahalchhari', 'Manikchhari', 'Matiranga', 'Panchhari', 'Ramgarh'],
                'Bandarban' => ['Alikadam', 'Bandarban Sadar', 'Lama', 'Naikhongchhari', 'Rowangchhari', 'Ruma', 'Thanchi'],
            ],
            'Rajshahi' => [
                'Rajshahi' => ['Bagha', 'Bagmara', 'Boalia', 'Charghat', 'Durgapur', 'Godagari', 'Matihar', 'Mohanpur', 'Paba', 'Puthia', 'Rajpara', 'Shah Makhdum', 'Tanore'],
                'Bogura' => ['Adamdighi', 'Bogura Sadar', 'Dhunat', 'Dhupchanchia', 'Gabtali', 'Kahaloo', 'Nandigram', 'Sariakandi', 'Shajahanpur', 'Sherpur', 'Shibganj', 'Sonatola'],
                'Pabna' => ['Atgharia', 'Bera', 'Bhangura', 'Chatmohar', 'Faridpur', 'Ishwardi', 'Pabna Sadar', 'Santhia', 'Sujanagar'],
                'Natore' => ['Bagatipara', 'Baraigram', 'Gurudaspur', 'Lalpur', 'Naldanga', 'Natore Sadar', 'Singra'],
                'Naogaon' => ['Atrai', 'Badalgachhi', 'Dhamoirhat', 'Manda', 'Mahadebpur', 'Naogaon Sadar', 'Niamatpur', 'Patnitala', 'Porsha', 'Raninagar', 'Sapahar'],
                'Chapainawabganj' => ['Bholahat', 'Chapainawabganj Sadar', 'Gomastapur', 'Nachole', 'Shibganj'],
                'Sirajganj' => ['Belkuchi', 'Chauhali', 'Kamarkhanda', 'Kazipur', 'Raiganj', 'Shahzadpur', 'Sirajganj Sadar', 'Tarash', 'Ullahpara'],
                'Joypurhat' => ['Akkelpur', 'Joypurhat Sadar', 'Kalai', 'Khetlal', 'Panchbibi'],
            ],
            'Khulna' => [
                'Khulna' => ['Batiaghata', 'Dacope', 'Daulatpur', 'Dighalia', 'Dumuria', 'Khalishpur', 'Khan Jahan Ali', 'Khulna Sadar', 'Koyra', 'Paikgachha', 'Phultala', 'Rupsa', 'Sonadanga', 'Terokhada'],
                'Jessore' => ['Abhaynagar', 'Bagherpara', 'Chaugachha', 'Jhikargachha', 'Jessore Sadar', 'Keshabpur', 'Manirampur', 'Sharsha'],
                'Kushtia' => ['Bheramara', 'Daulatpur', 'Khoksa', 'Kumarkhali', 'Kushtia Sadar', 'Mirpur'],
                'Satkhira' => ['Assasuni', 'Debhata', 'Kalaroa', 'Kaliganj', 'Satkhira Sadar', 'Shyamnagar', 'Tala'],
                'Bagerhat' => ['Bagerhat Sadar', 'Chitalmari', 'Fakirhat', 'Kachua', 'Mollahat', 'Mongla', 'Morrelganj', 'Rampal', 'Sarankhola'],
                'Meherpur' => ['Gangni', 'Meherpur Sadar', 'Mujibnagar'],
                'Narail' => ['Kalia', 'Lohagara', 'Narail Sadar'],
                'Chuadanga' => ['Alamdanga', 'Chuadanga Sadar', 'Damurhuda', 'Jibannagar'],
                'Jhenaidah' => ['Harinakunda', 'Jhenaidah Sadar', 'Kaliganj', 'Kotchandpur', 'Maheshpur', 'Shailkupa'],
                'Magura' => ['Magura Sadar', 'Mohammadpur', 'Shalikha', 'Sreepur'],
            ],
            'Barishal' => [
                'Barishal' => ['Agailjhara', 'Babuganj', 'Bakerganj', 'Banaripara', 'Barishal Sadar', 'Gournadi', 'Hizla', 'Mehendiganj', 'Muladi', 'Wazirpur'],
                'Patuakhali' => ['Bauphal', 'Dashmina', 'Dumki', 'Galachipa', 'Kalapara', 'Mirzaganj', 'Patuakhali Sadar', 'Rangabali'],
                'Bhola' => ['Bhola Sadar', 'Borhanuddin', 'Charfasson', 'Daulatkhan', 'Lalmohan', 'Manpura', 'Tazumuddin'],
                'Jhalakathi' => ['Jhalakathi Sadar', 'Kathalia', 'Nalchity', 'Rajapur'],
                'Pirojpur' => ['Bhandaria', 'Kawkhali', 'Mathbaria', 'Nazirpur', 'Nesarabad', 'Pirojpur Sadar', 'Zianagar'],
                'Barguna' => ['Amtali', 'Bamna', 'Barguna Sadar', 'Betagi', 'Patharghata', 'Taltali'],
            ],
            'Sylhet' => [
                'Sylhet' => ['Balaganj', 'Beanibazar', 'Bishwanath', 'Companiganj', 'Dakshin Surma', 'Fenchuganj', 'Golapganj', 'Gowainghat', 'Jaintiapur', 'Kanaighat', 'Osmani Nagar', 'South Surma', 'Sylhet Sadar', 'Zakiganj'],
                'Moulvibazar' => ['Barlekha', 'Juri', 'Kamalganj', 'Kulaura', 'Moulvibazar Sadar', 'Rajnagar', 'Sreemangal'],
                'Habiganj' => ['Ajmiriganj', 'Bahubal', 'Baniachong', 'Chunarughat', 'Habiganj Sadar', 'Lakhai', 'Madhabpur', 'Nabiganj', 'Sayestaganj'],
                'Sunamganj' => ['Bishwamvarpur', 'Chhatak', 'Derai', 'Dharamapasha', 'Dowarabazar', 'Jagannathpur', 'Jamalganj', 'Sulla', 'Sunamganj Sadar', 'Tahirpur'],
            ],
            'Rangpur' => [
                'Rangpur' => ['Badarganj', 'Gangachara', 'Kaunia', 'Mithapukur', 'Pirgachha', 'Pirganj', 'Rangpur Sadar', 'Taraganj'],
                'Dinajpur' => ['Birampur', 'Birganj', 'Biral', 'Bochaganj', 'Chirirbandar', 'Dinajpur Sadar', 'Fulbari', 'Ghoraghat', 'Hakimpur', 'Kaharole', 'Khansama', 'Nawabganj', 'Parbatipur'],
                'Thakurgaon' => ['Baliadangi', 'Haripur', 'Pirganj', 'Ranisankail', 'Thakurgaon Sadar'],
                'Panchagarh' => ['Atwari', 'Boda', 'Debiganj', 'Panchagarh Sadar', 'Tetulia'],
                'Kurigram' => ['Bhurungamari', 'Char Rajibpur', 'Chilmari', 'Kurigram Sadar', 'Nageshwari', 'Phulbari', 'Rajarhat', 'Raumari', 'Ulipur'],
                'Lalmonirhat' => ['Aditmari', 'Hatibandha', 'Kaliganj', 'Lalmonirhat Sadar', 'Patgram'],
                'Nilphamari' => ['Dimla', 'Domar', 'Jaldhaka', 'Kishoreganj', 'Nilphamari Sadar', 'Saidpur'],
                'Gaibandha' => ['Fulchhari', 'Gaibandha Sadar', 'Gobindaganj', 'Palashbari', 'Sadullapur', 'Saghata', 'Sundarganj'],
            ],
            'Mymensingh' => [
                'Mymensingh' => ['Bhaluka', 'Dhobaura', 'Fulbaria', 'Gafargaon', 'Gauripur', 'Haluaghat', 'Ishwarganj', 'Muktagachha', 'Mymensingh Sadar', 'Nandail', 'Phulpur', 'Trishal'],
                'Jamalpur' => ['Bakshiganj', 'Dewanganj', 'Islampur', 'Jamalpur Sadar', 'Madarganj', 'Melandaha', 'Sarishabari'],
                'Netrokona' => ['Atpara', 'Barhatta', 'Durgapur', 'Kalmakanda', 'Kendua', 'Khaliajuri', 'Madan', 'Mohanganj', 'Netrokona Sadar', 'Purbadhala'],
                'Sherpur' => ['Jhenaigati', 'Nakla', 'Nalitabari', 'Sherpur Sadar', 'Sreebardi'],
            ],
        ];
    }
}

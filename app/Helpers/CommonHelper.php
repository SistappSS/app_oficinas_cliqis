<?php

use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Users\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function getUserInitials($name)
{
    $words = explode(' ', $name);
    $initials = strtoupper(mb_substr($words[0], 0, 1) . (isset($words[1]) ? mb_substr(end($words), 0, 1) : ''));

    return $initials;
}

function isAdmin($auth)
{
    if ($auth->hasRole('admin')) {

    } else {
        $newData = [];

        return $newData;
    }
}

function isActiveRoute($route)
{
    if (Route::currentRouteName() == $route) {
        return true;
    }
}

function isActive($routes)
{
    if (is_array($routes)) {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route) {
                return 'bg-blue-700 text-white shadow-md ring-2 ring-blue-300';
            }
        }
        return '';
    } else {
        return Route::currentRouteName() == $routes ? 'bg-blue-700 text-white shadow-md ring-2 ring-blue-300' : 'text-[#64847b] bg-white hover:bg-blue-700 hover:text-white hover:shadow-md hover:ring-2 hover:ring-blue-300';
    }
}

function humansDate($data)
{
    $humansDate = Carbon::parse($data)->diffForHumans();

    return $humansDate;
}

function generateImg($folder, $oldImage = null)
{
    $request = request();

    if ($oldImage && Storage::disk('public')->exists($oldImage)) {
        Storage::disk('public')->delete($oldImage);
    }

    $imageData = $request->file('image');  // Corrigido para buscar o arquivo

    // Armazena a imagem no caminho específico
    $caminhoImagem = $imageData->store("assets/img/{$folder}", 'public');

    // Obtém o conteúdo da imagem e cria uma nova a partir dela
    $imageContent = Storage::disk('public')->get($caminhoImagem);
    $image = imagecreatefromstring($imageContent);

    if ($image !== false) {
        $resizedImage = imagescale($image, 150, 150);

        ob_start();
        imagejpeg($resizedImage);
        $imagemBase64 = base64_encode(ob_get_clean());

        imagedestroy($resizedImage);
    }

    imagedestroy($image);

    return $imagemBase64;  // Retorna apenas a string base64
}

function brlPrice($value)
{
    $value = number_format($value, 2, ',', '.');

    $value = "R$ {$value}";
    return $value;
}

function decimalPrice(string $v): string {
    $v = preg_replace('/[^\d,.-]/', '', $v); // remove símbolos e espaços
    $v = str_replace('.', '', $v);           // remove separador de milhar
    $v = str_replace(',', '.', $v);          // troca vírgula por ponto
    return number_format((float)$v, 2, '.', '');
}

function generateEmail($fullName, $domain = 'sistapp.com.br')
{
    $name = strtolower($fullName);

    $name = preg_replace('/[áàãâä]/u', 'a', $name);
    $name = preg_replace('/[éèêë]/u', 'e', $name);
    $name = preg_replace('/[íìîï]/u', 'i', $name);
    $name = preg_replace('/[óòõôö]/u', 'o', $name);
    $name = preg_replace('/[úùûü]/u', 'u', $name);
    $name = preg_replace('/[ç]/u', 'c', $name);

    $parts = explode(' ', $name);

    $firstName = $parts[0];

    $lastName = end($parts);

    $email = "{$firstName}.{$lastName}@{$domain}";

    return $email;
}

function generatePassword($fullName, $phone)
{
    $nameParts = explode(' ', $fullName);

    $firstName = ucfirst($nameParts[0]);
    $lastName = end($nameParts);

    $firstTwo = substr($firstName, 0, 2);

    $lastTwo = substr($lastName, -2);

    $lastFourPhone = substr($phone, -4);

    $newPassword = "#!{$firstTwo}{$lastFourPhone}{$lastTwo}#!";

    $password = Hash::make($newPassword);

    return $password;
}

function checkCustomerSistappId()
{
    $auth = Auth::user();

    if ($auth->customerLogin) {
        $customerSistappId = $auth->customerLogin->customer_sistapp_id;

        if (!empty($customerSistappId)) {
            return $customerSistappId;
        }
    }

    return null;
}

function getRolesForUser()
{
    $auth = Auth::user();

    // Se for admin, retorna todas as roles
    if ($auth->hasRole('admin')) {
        return Role::all(); // Lista todas as roles cadastradas
    }

    // Se não for admin, define as roles permitidas
    $roles = ['free', 'customer_customer_cliqis'];

    // Se o usuário já tiver a role 'free', mostra apenas 'free' e 'customer_customer_cliqis'
    if ($auth->hasRole('free')) {
        $roles = ['free', 'customer_customer_cliqis'];
    } else {
        $roles[] = $auth->getRoleNames()->first(); // Adiciona a role atual do usuário
    }

    return Role::whereIn('name', $roles)->get();
}

function generateCustomerSistappId($model)
{
    do {
        $randomNumber = mt_rand(100000, 999999);
        $customerSistappId = "sist_{$randomNumber}";

        $exists = $model->where('customer_sistapp_id', $customerSistappId)->exists();

    } while ($exists);

    return $customerSistappId;
}

function upperText($data)
{
    $data = $data;

    $upper = mb_strtoupper($data, 'UTF-8');

    return $upper;
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;

class DomainController extends Controller
{
    public function index()
    {
        $tenant = tenant();
        $domains = $tenant->domains()->get();
        $baseDomain = config('tenancy.subdomain_base', 'dcrayons.app');
        $primarySubdomain = $tenant->id . '.' . $baseDomain;

        return view('admin.settings.domains', compact('domains', 'primarySubdomain', 'baseDomain'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?(\.[a-zA-Z]{2,})+$/',
                'unique:domains,domain',
            ],
        ], [
            'domain.regex' => 'Please enter a valid domain name (e.g., mystore.com).',
            'domain.unique' => 'This domain is already in use.',
        ]);

        tenant()->domains()->create(['domain' => $validated['domain']]);

        return back()->with('success', "Domain '{$validated['domain']}' added. Please update your DNS settings.");
    }

    public function destroy(Request $request)
    {
        $tenant = tenant();

        // Cannot remove the last domain
        if ($tenant->domains()->count() <= 1) {
            return back()->with('error', 'Cannot remove the last domain.');
        }

        $domain = $tenant->domains()->where('id', $request->input('domain_id'))->first();

        // Cannot remove the auto-generated subdomain
        $baseDomain = config('tenancy.subdomain_base', 'dcrayons.app');
        if ($domain && str_ends_with($domain->domain, '.' . $baseDomain)) {
            return back()->with('error', 'Cannot remove the platform subdomain.');
        }

        if ($domain) {
            $domainName = $domain->domain;
            $domain->delete();
            return back()->with('success', "Domain '{$domainName}' removed.");
        }

        return back()->with('error', 'Domain not found.');
    }

    /**
     * Check DNS verification for a custom domain.
     */
    public function verifyDns(Request $request)
    {
        $domain = $request->input('domain');
        $expectedIp = config('tenancy.server_ip', '15.207.133.144');

        // Check A record
        $records = @dns_get_record($domain, DNS_A);
        $aRecordMatch = false;
        if ($records) {
            foreach ($records as $record) {
                if (isset($record['ip']) && $record['ip'] === $expectedIp) {
                    $aRecordMatch = true;
                    break;
                }
            }
        }

        // Check CNAME
        $cnameRecords = @dns_get_record($domain, DNS_CNAME);
        $cnameMatch = false;
        $baseDomain = config('tenancy.subdomain_base', 'dcrayons.app');
        if ($cnameRecords) {
            foreach ($cnameRecords as $record) {
                if (isset($record['target']) && str_contains($record['target'], $baseDomain)) {
                    $cnameMatch = true;
                    break;
                }
            }
        }

        return response()->json([
            'verified' => $aRecordMatch || $cnameMatch,
            'a_record' => $aRecordMatch,
            'cname' => $cnameMatch,
            'message' => ($aRecordMatch || $cnameMatch)
                ? 'DNS verified successfully!'
                : "DNS not yet pointing to our server. Add an A record pointing to {$expectedIp} or a CNAME pointing to {$baseDomain}.",
        ]);
    }
}

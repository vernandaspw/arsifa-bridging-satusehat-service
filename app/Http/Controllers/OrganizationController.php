<?php

namespace App\Http\Controllers;

use App\DTO\OrganizationDTO;
use App\Models\Satusehat\SatusehatOrganization;
use App\Models\User;
use App\Services\SatuSehat\OrganizationService;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function getTree(Request $req)
    {
        $user = User::auth($req);

        $organizations = SatusehatOrganization::all();

        $tree = $this->buildTree($organizations);

        return response()->json([
            'msg' => 'success',
            'data' => $tree,
        ], 200);
    }

    private function buildTree($elements, $parentId = null)
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element->part_of === $parentId) {
                $children = $this->buildTree($elements, $element->organization_id);
                if ($children) {
                    $element->children = $children;
                }
                $branch[] = $element;
            }
        }

        return $branch;
    }

    public function detail(Request $req)
    {
        try {
            $organization_id = $req->query('organization_id');
        $organization = SatusehatOrganization::where('organization_id', $organization_id)->first();

        $ssRes = OrganizationService::getByID($organization_id);

        $ss = [
            'id' => $ssRes['id'],
            'identifierValue' => $ssRes['identifier'][0]['value'],
            'resourceType' => $ssRes['resourceType'],
            'nama' => $ssRes['name'],
            'partOf' => explode('/', $ssRes['partOf']['reference'])[1],
            'typeCode' => $ssRes['type'][0]['coding'][0]['code'],
            'status' => $ssRes['active'],
        ];

        //  tes service
        if (!$organization && !$ss['id']) {
            return response()->json([
                'msg' => 'error',
            ]);
        }

        $organizationType = OrganizationDTO::getTypes();

        $parent = SatusehatOrganization::where('organization_id', $organization->part_of)->first();
        $children = SatusehatOrganization::where('part_of', $organization_id)->get();

        return response()->json([
            'msg' => 'success',
            'data' => [
                'organization' => $organization,
                'ss' => $ss,
                'ssResponse' => $ssRes,
                'organizationType' => $organizationType,
                'parent' => $parent,
                'children' => $children,
            ],
        ]);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'Terjadi kesalahan server satusehat',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
    public function create(Request $request)
    {
        $user = User::auth($request);
        $type_code = $request->type_code;
        $identifier_value = $request->identifier_value;
        $name = $request->name;
        $active = $request->active ? true : false;
        $part_of = $request->part_of;

        // cari data organization type
        foreach (OrganizationDTO::getTypes() as $type) {
            if ($type['coding_code'] == $type_code) {
                $dataType = $type;
            }
        }

        $body = [
            'identifier_value' => $identifier_value,
            'name' => $name,
            'coding_code' => $dataType['coding_code'],
            'coding_display' => $dataType['coding_display'],
            'active' => $active,
        ];

        if ($request->filled('part_of')) {
            $body['part_of'] = $part_of;
        }

        try {
            // send API
            $data = OrganizationService::create($body);

            if ($data->getStatusCode() == 500) {
                return response()->json([
                    'msg' => json_decode($data->getContent())->msg,
                    'errors' => json_decode($data->getContent())->errors != null ? json_decode($data->getContent())->errors : null,
                ]);
            }

            // Send DB
            $organization = SatusehatOrganization::create([
                'organization_id' => $data['id'],
                'active' => $data['active'],
                'name' => $data['name'],
                'part_of' => $body['part_of'] ?? '',
                'created_by' => $user ?? 'system',
            ]);

            $message = 'New item has been created successfully.';
            return response()->json([
                'msg' => $message,
                'data' => $organization,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'Terjadi kesalahan server satusehat',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $organization_id = $request->organization_id;
        $type_code = $request->type_code;
        $identifier_value = $request->identifier_value;
        $name = $request->name;
        $active = $request->active ? true : false;
        $part_of = $request->part_of;

        $organization = SatusehatOrganization::where('organization_id', $organization_id)->first();
        // cari data organization type
        foreach (OrganizationDTO::getTypes() as $type) {
            if ($type['coding_code'] == $type_code) {
                $dataType = $type;
            }
        }

        $body = [
            'id' => $organization->organization_id,
            'identifier_value' => $identifier_value,
            'name' => $name,
            'coding_code' => $dataType['coding_code'],
            'coding_display' => $dataType['coding_display'],
            'active' => $active ? true : false
        ];

        if ($request->filled('part_of')) {
            $body['part_of'] = $part_of;
        }

        try {
            // send API
            $data = OrganizationService::update($body);

            // Send DB
            $organization->update([
                'organization_id' => $data['id'],
                'active' => $data['active'],
                'name' => $data['name'],
                'part_of' => $body['part_of'] ?? '',
                'updated_by' => auth()->user()->id ?? 'system'
            ]);

            $message = 'Data has been updated successfully.';
            return response()->json([
                'msg' => $message,
                'data' => $organization,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'Terjadi kesalahan server satusehat',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}

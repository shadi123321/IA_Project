<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * ================================
 *   API GENERAL INFORMATION
 * ================================
 *
 * @OA\Info(
 *      title="My Laravel API",
 *      version="1.0.0",
 *      description="API Documentation with Swagger"
 * )
 *
 * @OA\Server(
 *      url="http://localhost:8000/api",
 *      description="Local API server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin related operations"
 * )
 *
 * @OA\Tag(
 *     name="Admin / Employees",
 *     description="CRUD operations for employees under admin"
 * )
 */
class SwaggerController extends Controller
{
    /* ============================================================
     *                   ADMIN → EMPLOYEES CRUD
     * ============================================================*/

    /**
     * @OA\Post(
     *     path="/storeEmployee",
     *     summary="Create a new employee",
     *     description="Create employee under government entity",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","government_entity_id"},
     *             @OA\Property(property="name", type="string", example="Ahmad Ali"),
     *             @OA\Property(property="email", type="string", example="ahmad@example.com"),
     *             @OA\Property(property="password", type="string", example="secret123"),
     *             @OA\Property(property="government_entity_id", type="integer", example=3)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Employee created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function storeEmployeeDoc() {}

    /**
     * @OA\Get(
     *     path="/indexEmployees",
     *     summary="List all employees for a government entity",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="government_entity_id",
     *         in="query",
     *         required=true,
     *         description="Government entity ID",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Employees list returned successfully"),
     *     @OA\Response(response=422, description="Invalid request")
     * )
     */
    public function indexEmployeesDoc() {}

    /**
     * @OA\Get(
     *     path="/showEmployee/{id}",
     *     summary="Get employee details",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Employee ID",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Employee details returned"),
     *     @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function showEmployeeDoc() {}

    /**
     * @OA\Put(
     *     path="/updateEmployee/{id}",
     *     summary="Update employee information",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Employee ID",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Name"),
     *             @OA\Property(property="email", type="string", example="newmail@example.com"),
     *             @OA\Property(property="government_entity_id", type="integer", example=5)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Employee updated successfully"),
     *     @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function updateEmployeeDoc() {}

    /**
     * @OA\Delete(
     *     path="/deleteEmployee/{id}",
     *     summary="Delete an employee",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Employee ID",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Employee deleted successfully"),
     *     @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function deleteEmployeeDoc() {}
    
    /* ============================================================
     *                   EMPLOYEE → CHANGE COMPLAINT STATUS
     * ============================================================*/

    /**
     * @OA\Put(
     *     path="/changeStatus",
     *     summary="Change complaint status",
     *     description="Allows an employee to change the status of a complaint according to allowed transitions.",
     *     tags={"EMPLOYEE"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reference_number","status","user_id"},
     *             @OA\Property(property="reference_number", type="string", example="CMP12345", description="Unique reference number of the complaint"),
     *             @OA\Property(property="status", type="string", enum={"new","processing","resolved","rejected"}, example="processing", description="New status for the complaint"),
     *             @OA\Property(property="note", type="string", example="Reviewed by admin", description="Optional note for status change"),
     *             @OA\Property(property="user_id", type="integer", example=7, description="ID of the employee performing the action")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Status changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Status changed successfully"),
     *             @OA\Property(property="complain", type="object", description="Updated complaint object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or locked complaint",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Complaint is locked by John. Try again after 10 minutes.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed (invalid input data)"
     *     )
     * )
     */
    public function changeStatusDoc() {}
        /* ============================================================
     *                   EMPLOYEE → SHOW COMPLAINT
     * ============================================================*/

    /**
     * @OA\Get(
     *     path="/showComplaint/{reference_number}",
     *     summary="Get detailed complaint information",
     *     description="Retrieve a complaint by reference number along with its full history and the employees who handled it.",
     *     tags={"EMPLOYEE"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="reference_number",
     *         in="path",
     *         required=true,
     *         description="Unique reference number of the complaint",
     *         @OA\Schema(type="string", example="CMP12345")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Complaint details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="complain", type="object",
     *                 @OA\Property(property="reference_number", type="string", example="CMP12345"),
     *                 @OA\Property(property="status", type="string", example="processing"),
     *                 @OA\Property(property="description", type="string", example="Complaint description"),
     *                 @OA\Property(property="location", type="string", example="Building A"),
     *                 @OA\Property(property="histories", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="status", type="string", example="new"),
     *                         @OA\Property(property="note", type="string", example="Initial review"),
     *                         @OA\Property(property="changed_at", type="string", format="date-time", example="2025-12-09T14:00:00Z"),
     *                         @OA\Property(property="handled_by", type="object",
     *                             @OA\Property(property="id", type="integer", example=7),
     *                             @OA\Property(property="employee", type="string", example="Ahmad Ali")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Complaint not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Complaint not found")
     *         )
     *     )
     * )
     */
    public function showComplaintDoc() {}
    /**
 * @OA\Get(
 *     path="/indexByEntity",
 *     summary="List complaints by user's government entity",
 *     description="Retrieve all complaints related to the government entity of a given user.",
 *     tags={"EMPLOYEE"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="user",
 *         in="query",
 *         required=true,
 *         description="ID of the user whose government entity complaints should be retrieved",
 *         @OA\Schema(type="integer", example=7)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Complaints retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="sector", type="integer", example=3),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="reference_number", type="string", example="CMP12345"),
 *                     @OA\Property(property="status", type="string", example="processing"),
 *                     @OA\Property(property="description", type="string", example="Complaint description"),
 *                     @OA\Property(property="location", type="string", example="Building A"),
 *                     @OA\Property(property="government_entity_id", type="integer", example=3)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="User not found")
 *         )
 *     )
 * )
 */
public function indexByEntityDoc() {}
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected array $fileTypes = [
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx', 'txt', 'odt'],
            'mimes' => [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt' => 'text/plain',
                'odt' => 'application/vnd.oasis.opendocument.text',
            ],
            'size_range' => [50000, 5000000], // 50KB - 5MB
        ],
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'mimes' => [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
            ],
            'size_range' => [100000, 10000000], // 100KB - 10MB
        ],
        'video' => [
            'extensions' => ['mp4', 'avi', 'mov', 'webm'],
            'mimes' => [
                'mp4' => 'video/mp4',
                'avi' => 'video/x-msvideo',
                'mov' => 'video/quicktime',
                'webm' => 'video/webm',
            ],
            'size_range' => [5000000, 100000000], // 5MB - 100MB
        ],
        'code' => [
            'extensions' => ['js', 'php', 'py', 'java', 'cpp', 'html', 'css', 'json', 'xml', 'sql', 'sh'],
            'mimes' => [
                'js' => 'text/javascript',
                'php' => 'text/x-php',
                'py' => 'text/x-python',
                'java' => 'text/x-java',
                'cpp' => 'text/x-c++',
                'html' => 'text/html',
                'css' => 'text/css',
                'json' => 'application/json',
                'xml' => 'application/xml',
                'sql' => 'application/sql',
                'sh' => 'text/x-shellscript',
            ],
            'size_range' => [1000, 500000], // 1KB - 500KB
        ],
        'spreadsheet' => [
            'extensions' => ['xlsx', 'xls', 'csv', 'ods'],
            'mimes' => [
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xls' => 'application/vnd.ms-excel',
                'csv' => 'text/csv',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            ],
            'size_range' => [10000, 5000000], // 10KB - 5MB
        ],
        'archive' => [
            'extensions' => ['zip', 'rar', 'tar', 'gz', '7z'],
            'mimes' => [
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'tar' => 'application/x-tar',
                'gz' => 'application/gzip',
                '7z' => 'application/x-7z-compressed',
            ],
            'size_range' => [100000, 50000000], // 100KB - 50MB
        ],
    ];

    protected array $projectNames = [
        'Project Alpha', 'Website Redesign', 'Mobile App', 'API Documentation',
        'Marketing Campaign', 'Q4 Reports', 'Customer Data', 'Product Launch',
        'Research Files', 'Design Assets', 'Development', 'Testing Suite',
    ];

    protected array $codeProjects = [
        'React App', 'Laravel API', 'Python Scripts', 'Data Analysis',
        'Machine Learning', 'DevOps Config', 'Database Schemas', 'Frontend Components',
    ];

    public function definition(): array
    {
        $isFolder = $this->faker->boolean(20);

        return $isFolder ? $this->folderDefinition() : $this->fileDefinition();
    }

    public function fileDefinition()
    {
        $type = $this->faker->randomElement(array_keys($this->fileTypes));
        $typeConfig = $this->fileTypes[$type];
        $extension = $this->faker->randomElement($typeConfig['extensions']);
        $mimeType = $typeConfig['mimes'][$extension];

        $name = $this->generateFileName($type, $extension);

        return [
            'name' => $name,
            'path' => 'files/'.Str::random(8).'/'.Str::slug(pathinfo($name, PATHINFO_FILENAME)).'.'.$extension,
            'size' => $this->faker->numberBetween($typeConfig['size_range'][0], $typeConfig['size_range'][1]),
            'mime_type' => $mimeType,
            'is_folder' => false,
            'owner_id' => User::factory(),
            'parent_folder_id' => null,

        ];

    }

    public function folderDefinition()
    {
        $name = $this->faker->randomElement([
            'Documents', 'Images', 'Videos', 'Downloads', 'Backups', 'Archives',
        ]);

        return [
            'name' => $name,
            'path' => null,
            'size' => 0,
            'mime_type' => null,
            'is_folder' => true,
            'owner_id' => User::factory(),
            'parent_folder_id' => null,
        ];

    }

    protected function generateFileName($type, $extension)
    {
        $templates = [
            'document' => [
                'Report {month} {year}',
                '{project} Documentation',
                'Meeting Notes {date}',
                '{company} Proposal',
                'Contract {number}',
                'Invoice {number}',
                '{project} Specifications',
            ],
            'image' => [
                'Screenshot {date}',
                '{project} Logo',
                'Banner {number}',
                'Photo {number}',
                '{product} Mockup',
                'Design {version}',
                'Icon {name}',
            ],
            'video' => [
                '{project} Demo',
                'Tutorial {number}',
                'Presentation {date}',
                'Recording {date}',
                'Webinar {topic}',
                'Product Video {version}',
            ],
            'code' => [
                'index',
                'app',
                'main',
                'utils',
                'config',
                'test_{name}',
                '{component}_component',
                '{module}_module',
                'api_{endpoint}',
                'model_{name}',
            ],
            'spreadsheet' => [
                'Budget {year}',
                'Sales Report {month}',
                'Inventory {date}',
                'Analytics {quarter}',
                'Employee List',
                'Financial Statement {year}',
                'Data Export {date}',
            ],
            'archive' => [
                'Backup {date}',
                '{project} Archive',
                'Release {version}',
                'Assets Bundle',
                'Documents {month} {year}',
                'Export {date}',
            ],
        ];

        $template = $this->faker->randomElement($templates[$type] ?? ['File {number}']);

        $replacements = [
            '{date}' => $this->faker->date('Y-m-d'),
            '{month}' => $this->faker->monthName(),
            '{year}' => $this->faker->year(),
            '{number}' => $this->faker->numberBetween(1000, 9999),
            '{project}' => $this->faker->randomElement($this->projectNames),
            '{company}' => $this->faker->company(),
            '{version}' => 'v'.$this->faker->numberBetween(1, 9).'.'.$this->faker->numberBetween(0, 9),
            '{name}' => $this->faker->word(),
            '{component}' => $this->faker->randomElement(['user', 'auth', 'dashboard', 'profile', 'settings']),
            '{module}' => $this->faker->randomElement(['payment', 'notification', 'analytics', 'export']),
            '{endpoint}' => $this->faker->randomElement(['users', 'posts', 'products', 'orders']),
            '{quarter}' => 'Q'.$this->faker->numberBetween(1, 4),
            '{topic}' => $this->faker->randomElement(['Introduction', 'Advanced Features', 'Best Practices']),
            '{product}' => $this->faker->randomElement(['App', 'Website', 'Dashboard', 'Platform']),
        ];

        $name = str_replace(array_keys($replacements), array_values($replacements), $template);

        return $name.'.'.$extension;
    }
}

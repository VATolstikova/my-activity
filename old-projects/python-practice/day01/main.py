"""
Day 1: Скелет Task Tracker.

Сегодня создаём базовый класс Task и учимся работать с датами.
"""
from dataclasses import dataclass, field
from datetime import datetime
from enum import Enum


class Priority(Enum):
    """Приоритет задачи."""
    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"


class Status(Enum):
    """Статус задачи."""
    TODO = "todo"
    IN_PROGRESS = "in_progress"
    DONE = "done"


@dataclass
class Task:
    """Одна задача в трекере."""
    id: int
    title: str
    description: str = ""
    priority: Priority = Priority.MEDIUM
    status: Status = Status.TODO
    created_at: datetime = field(default_factory=datetime.now)
    deadline: datetime | None = None

    def mark_done(self) -> None:
        """Отметить задачу выполненной."""
        self.status = Status.DONE

    def is_overdue(self) -> bool:
        """Просрочена ли задача."""
        if self.deadline is None or self.status == Status.DONE:
            return False
        return datetime.now() > self.deadline

    def __str__(self) -> str:
        mark = "OK" if self.status == Status.DONE else "..."
        return f"{mark} [{self.id}] {self.title} ({self.priority.value})"


if __name__ == "__main__":
    task = Task(
        id=1,
        title="Допилить 70-дневный челлендж",
        description="Начать с Task Tracker",
        priority=Priority.HIGH,
    )
    print(task)
    task.mark_done()
    print(task)
    print(f"Просрочена: {task.is_overdue()}")